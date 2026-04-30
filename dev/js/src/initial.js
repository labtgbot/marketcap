(function (window, navigator, _, Vue, GeckoClient) {
    'use strict';

    // UTILS

    const utils = GeckoClient.utils = {};

    utils.isValidDate = date => _.isDate(date) && _.isFinite(date.getTime());

    utils.isMobileUserAgent = () => /mobile/i.test(navigator.userAgent);

    utils.validURLString = (url, base) => {
        if (!url) return null;

        try {
            return (new URL(url, base)).toString();
        } catch (err) {
            return null;
        }
    };

    utils.getHostFromURL = (url, removeW3) => {
        try {
            url = new URL(url);
            return removeW3 ? url.hostname.replace('www.', '') : url.hostname;
        } catch (err) {
            return null;
        }
    }

    utils.getPathFromURL = (url, removeSlash) => {
        try {
            url = new URL(url);
            if (removeSlash === true) return url.pathname.replace(/^\/+|\/+$/g, '');
            if (removeSlash === 'left' || removeSlash === 'start') return url.pathname.replace(/^\/+/g, '');
            if (removeSlash === 'right' || removeSlash === 'end') return url.pathname.replace(/\/+$/g, '');
            return url.pathname;
        } catch (err) {
            return null;
        }
    }

    utils.bitcointalkThreadUrl = id => {
        if (id) {
            const url = new URL('https://bitcointalk.org/index.php');
            url.searchParams.set('topic', id);
            return url.toString();
        }
        return null;
    };

    // EXTEND GECKO CLIENT OBJECT

    // translate
    GeckoClient.__ = field => _.get(GeckoClient.translation, field, field);

    // preference manager uses persistent "Local Storage" object
    GeckoClient.preferences = {
        prefix: 'GeckoClient:',
        set: function (key, value)  {
            localStorage.setItem(this.prefix + key, JSON.stringify(value));
        },
        get: function (key) {
            let value = localStorage.getItem(this.prefix + key);
            return value === null ? null : JSON.parse(value);
        },
        remove: function (key) {
            localStorage.removeItem(this.prefix + key);
        },
        removeAll: function () {
            Object.keys(localStorage).forEach(function (key) {
                if (_.startsWith(key, this.prefix)) localStorage.removeItem(key);
            })
        },
        vsCurrency: function (id) {
            if (id === undefined) return this.get('vs_currency') || GeckoClient.defaultVsCurrencyId;
            this.set('vs_currency', id);
        },
        theme: function (theme) {
            if (theme === undefined) return this.get('theme') || (GeckoClient.vuetifyOptions.theme.dark ? 'dark' : 'light');
            this.set('theme', theme);
        },
        cookiesAccepted: function (accepted) {
            if (accepted === undefined) return this.get('cookies_accepted') || 0;
            if (accepted === true) return this.set('cookies_accepted', Date.now())
        }
    };

    GeckoClient.getOptions = (path, defaultValue) => _.get(GeckoClient.options, path, defaultValue);

    GeckoClient.getVuetifyOptions = () => {
        const options = _.cloneDeep(GeckoClient.vuetifyOptions);
        options.theme.dark = GeckoClient.preferences.theme() === 'dark';
        return options;
    }

    // collects fiat currencies from supported list
    GeckoClient.fiatCurrencies = {};
    GeckoClient.supportedVsCurrencies.forEach(c => {
        if (c.type === 'fiat') GeckoClient.fiatCurrencies[_.toUpper(c.id)] = c.name;
    });
    // checks if is fiat
    GeckoClient.isFiatCurrency = currency => _.get(GeckoClient.fiatCurrencies, _.toUpper(currency));

    // creates a "Intl.NumberFormat" instance based on the currency
    GeckoClient.getCurrencyFormatter = (locale, options, currency, isFiat) => {
        options = _.cloneDeep(options);
        // use "currency" style if is fiat (ISO 4217 supported)
        if (isFiat === true) {
            options.currency = currency;
            return Intl.NumberFormat(locale, options);
        }
        // other currencies use "decimal" style and avoid currency options
        options.style = 'decimal';
        delete options.currency;
        delete options.currencySign;
        delete options.currencyDisplay;
        return Intl.NumberFormat(locale, options);
    };

    // uses "Intl.NumberFormat" instance to format "value"
    GeckoClient.currencyFormat = (formatter, value, unit) => {
        value = parseFloat(value);
        if (_.isFinite(value) || formatter) return formatter.format(value) + (_.isString(unit) ? (' ' + unit) : '');
        return null;
    };

    // returns a custom link URL defined in "config/links.php"
    GeckoClient.getCustomLink = (type, id) => _.get(GeckoClient.links, [type, id]);

    // updates link canonical and open graph url tags
    GeckoClient.setCanonicalUrl = (url = location.href) => {
        const link = document.querySelector('link[rel="canonical"]')
        if (link) link.href = url;

        const og = document.querySelector('meta[rel="og:url"]')
        if (og) og.content = url;
    }

    // updates meta, open graph and twitter title tags
    GeckoClient.setTitle = text => {
        const website = GeckoClient.website;
        let content = _.isString(text) && text.length ? text + website.titleSeparator + website.title : website.title;

        const titleTag = document.querySelector('title');
        if (titleTag) titleTag.textContent = content; // "textContent" auto escapes

        content = _.escape(content);

        document.querySelectorAll('meta[name="twitter:title"], meta[property="og:title"]')
            .forEach(elem => elem.content = content);
    };

    // updates meta, open graph and twitter description tags
    // not being used
    GeckoClient.setDescription = description => {
        description = _.escape(description);

        document.querySelectorAll('meta[name="description"], meta[name="twitter:description"], meta[property="og:description"]')
            .forEach(elem => elem.content = description);
    };

    // FILTERS

    Vue.filter('uppercase', value => _.toUpper(value));

    Vue.filter('lowercase', value => _.toLower(value));


})(window, navigator, _, Vue, GeckoClient);
