(function (window, document, navigator, _, axios, GeckoClient) {
    'use strict';

    const config = GeckoClient.shareConfig || {};
    const endpoint = config.apiBaseUrl || '/api/share/resolve';
    const tokenPattern = /^[a-z0-9._-]{1,80}$/;

    function safeToken(value, fallback) {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '-').replace(/^[._-]+|[._-]+$/g, '');
        return tokenPattern.test(value) ? value : fallback;
    }

    function validRoute(route) {
        route = _.toString(route || '').trim();
        if (!route || route.charAt(0) !== '/' || route.indexOf('//') === 0) return false;
        if (/^[A-Za-z][A-Za-z0-9+.-]*:|[\\\r\n\u0000-\u001F]/.test(route)) return false;
        return /^\/[A-Za-z0-9._~/%:-]*(?:\?[A-Za-z0-9._~%=&:+,-]*)?$/.test(route) && route.length <= 200;
    }

    function currentRoute() {
        const routerRoute = _.get(GeckoClient, 'router.currentRoute.fullPath') || _.get(GeckoClient, 'router.currentRoute.path');
        if (validRoute(routerRoute)) return routerRoute;

        return (window.location.pathname || '/') + (window.location.search || '');
    }

    function routeUrl(route, baseUrl) {
        route = validRoute(route) ? route : '/';
        try {
            return new URL(route, baseUrl || window.location.origin + '/').toString();
        } catch (err) {
            return window.location.origin + route;
        }
    }

    function base64UrlEncode(value) {
        return window.btoa(unescape(encodeURIComponent(value)))
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=+$/g, '');
    }

    function base64UrlDecode(value) {
        value = _.toString(value || '').replace(/-/g, '+').replace(/_/g, '/');
        while (value.length % 4) value += '=';

        try {
            return decodeURIComponent(escape(window.atob(value)));
        } catch (err) {
            return null;
        }
    }

    function normalizeInviter(value) {
        value = _.toString(value || '').trim();
        if (!value) return null;
        if (/^telegram:[1-9][0-9]{0,19}$/.test(value)) return value;
        if (/^[1-9][0-9]{0,19}$/.test(value)) return 'telegram:' + value;
        return null;
    }

    function telegramUserInviter() {
        const id = _.get(GeckoClient, 'telegram.webApp.initDataUnsafe.user.id')
            || _.get(window, 'Telegram.WebApp.initDataUnsafe.user.id');
        return normalizeInviter(id);
    }

    function buildStartParam(payload) {
        payload = payload || {};
        const compact = {
            route: validRoute(payload.route) ? payload.route : '/',
            campaign: safeToken(payload.campaign, 'organic-share'),
            inviter: normalizeInviter(payload.inviter) || telegramUserInviter(),
            context: safeToken(payload.context, 'shared_view')
        };

        return 's_' + base64UrlEncode(JSON.stringify(compact));
    }

    function parseStartParam(startParam) {
        startParam = _.toString(startParam || '').trim();
        if (!/^s_[A-Za-z0-9_-]{1,510}$/.test(startParam)) return null;

        const json = base64UrlDecode(startParam.slice(2));
        if (!json) return null;

        try {
            const payload = JSON.parse(json);
            const route = _.toString(payload.route || '').trim();
            if (!validRoute(route)) return null;

            return {
                route: route,
                campaign: safeToken(payload.campaign, 'organic-share'),
                inviter: normalizeInviter(payload.inviter),
                context: safeToken(payload.context, 'shared_view')
            };
        } catch (err) {
            return null;
        }
    }

    function startParamFromLaunch() {
        const search = new URLSearchParams(window.location.search || '');
        return search.get('startapp')
            || search.get('tgWebAppStartParam')
            || _.get(GeckoClient, 'telegram.webApp.initDataUnsafe.start_param')
            || _.get(window, 'Telegram.WebApp.initDataUnsafe.start_param')
            || '';
    }

    function telegramShareUrl(startParam, fallbackRoute) {
        const username = _.toString(_.get(GeckoClient, 'runtime.telegram.botUsername') || '').replace(/^@+/, '');
        if (username) {
            return 'https://t.me/' + encodeURIComponent(username) + '?startapp=' + encodeURIComponent(startParam);
        }

        const url = new URL(routeUrl(
            fallbackRoute,
            _.get(GeckoClient, 'runtime.urls.telegramMiniApp') || _.get(GeckoClient, 'runtime.urls.publicWeb') || window.location.origin + '/'
        ));
        url.searchParams.set('startapp', startParam);
        return url.toString();
    }

    function webShareUrl(route, startParam) {
        const url = new URL(routeUrl(route, _.get(GeckoClient, 'runtime.urls.publicWeb') || window.location.origin + '/'));
        url.searchParams.set('startapp', startParam);
        return url.toString();
    }

    function normalizeCard(card) {
        card = card || {};
        const route = validRoute(card.route) ? card.route : currentRoute();
        const context = safeToken(card.context, 'shared_view');

        return {
            title: _.toString(card.title || 'TONBANKCARD market view').trim(),
            subtitle: _.toString(card.subtitle || '').trim(),
            body: _.toString(card.body || card.summary || '').trim(),
            route: validRoute(route) ? route : '/',
            campaign: safeToken(card.campaign, context.replace(/_/g, '-') || 'organic-share'),
            context: context,
            inviter: normalizeInviter(card.inviter) || telegramUserInviter(),
            freshness: _.toString(card.freshness || card.freshnessLabel || 'Data freshness unavailable').trim(),
            disclaimer: _.toString(card.disclaimer || 'Not financial advice').trim(),
            metrics: _.isArray(card.metrics) ? card.metrics.slice(0, 4) : []
        };
    }

    function shareLinks(card) {
        const normalized = normalizeCard(card);
        const startParam = buildStartParam(normalized);
        const webUrl = webShareUrl(normalized.route, startParam);

        return {
            startParam: startParam,
            webUrl: webUrl,
            telegramUrl: telegramShareUrl(startParam, normalized.route),
            route: normalized.route,
            card: normalized
        };
    }

    function emitShareEvent(name, card, target) {
        const properties = {
            source_route: card.route,
            share_context: card.context,
            campaign: card.campaign,
            route: card.route,
            share_target: target || null
        };

        if (GeckoClient.analytics) {
            GeckoClient.analytics.emit(name, properties);
        }
        if (name === 'share_started' && GeckoClient.achievements) {
            GeckoClient.achievements.track('share_started', properties);
        }
    }

    function share(card) {
        const links = shareLinks(card);
        const normalized = links.card;
        const activeTelegram = !!_.get(GeckoClient, 'telegram.active');
        const targetUrl = activeTelegram ? links.telegramUrl : links.webUrl;
        const text = normalized.subtitle ? normalized.title + ' - ' + normalized.subtitle : normalized.title;

        emitShareEvent('share_started', normalized, activeTelegram ? 'telegram' : 'web');

        if (GeckoClient.telegram && _.isFunction(GeckoClient.telegram.shareUrl)) {
            return GeckoClient.telegram.shareUrl(targetUrl, text)
                .then(shared => {
                    if (shared) return true;
                    return copyShareUrl(targetUrl);
                });
        }

        if (_.isFunction(navigator.share)) {
            return navigator.share({title: normalized.title, text: text, url: targetUrl})
                .then(() => true)
                .catch(() => false);
        }

        return copyShareUrl(targetUrl);
    }

    function copyShareUrl(url) {
        if (_.get(navigator, 'clipboard.writeText')) {
            return navigator.clipboard.writeText(url).then(() => true).catch(() => false);
        }
        return Promise.resolve(false);
    }

    function resolveLaunch() {
        const startParam = startParamFromLaunch();
        const parsed = parseStartParam(startParam);
        if (!parsed || !validRoute(parsed.route)) return Promise.resolve(null);

        emitShareEvent('referral_opened', parsed, 'launch');

        const router = GeckoClient.router;
        if (router && _.isFunction(router.push) && _.get(router, 'currentRoute.fullPath') !== parsed.route) {
            router.push(parsed.route).catch(() => {});
        }

        if (!axios) return Promise.resolve(parsed);

        return axios.get(endpoint, {params: {start_param: startParam}})
            .then(() => parsed)
            .catch(() => parsed);
    }

    GeckoClient.share = {
        buildStartParam: buildStartParam,
        parseStartParam: parseStartParam,
        normalizeCard: normalizeCard,
        shareLinks: shareLinks,
        share: share,
        resolveLaunch: resolveLaunch,
        validRoute: validRoute,
        currentRoute: currentRoute
    };

    window.setTimeout(resolveLaunch, 0);

})(window, document, navigator, _, axios, GeckoClient);
