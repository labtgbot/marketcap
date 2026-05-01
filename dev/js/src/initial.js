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

    const telegramThemeParamMap = {
        bg_color: '--tbc-tg-bg',
        secondary_bg_color: '--tbc-tg-secondary-bg',
        text_color: '--tbc-tg-text',
        hint_color: '--tbc-tg-hint',
        link_color: '--tbc-tg-link',
        button_color: '--tbc-tg-button',
        button_text_color: '--tbc-tg-button-text',
        header_bg_color: '--tbc-tg-header-bg',
        bottom_bar_bg_color: '--tbc-tg-bottom-bar-bg',
        accent_text_color: '--tbc-tg-accent-text',
        destructive_text_color: '--tbc-tg-destructive-text',
        section_bg_color: '--tbc-tg-section-bg',
        section_header_text_color: '--tbc-tg-section-header-text',
        subtitle_text_color: '--tbc-tg-subtitle-text'
    };

    const telegramVuetifyParamMap = {
        bg_color: 'background',
        secondary_bg_color: 'surface',
        text_color: 'text_primary',
        hint_color: 'text_muted',
        link_color: 'info',
        button_color: 'primary',
        destructive_text_color: 'low'
    };

    utils.isValidHexColor = value => _.isString(value) && /^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i.test(_.trim(value));

    utils.normalizeHexColor = value => {
        if (!utils.isValidHexColor(value)) return null;

        value = _.toUpper(_.trim(value));
        if (value.length === 4) {
            value = '#' + value.charAt(1) + value.charAt(1) + value.charAt(2) + value.charAt(2) + value.charAt(3) + value.charAt(3);
        }

        return value;
    };

    utils.getTelegramWebApp = () => _.get(window, 'Telegram.WebApp') || null;

    // EXTEND GECKO CLIENT OBJECT

    // translate
    GeckoClient.__ = field => _.get(GeckoClient.translation, field, field);

    GeckoClient.setDocumentThemeClass = theme => {
        document.documentElement.classList.toggle('tbc-theme-dark', theme === 'dark');
    };

    // preference manager uses persistent "Local Storage" object
    GeckoClient.preferences = {
        prefix: 'GeckoClient:',
        set: function (key, value)  {
            try {
                localStorage.setItem(this.prefix + key, JSON.stringify(value));
            } catch (err) {}
        },
        get: function (key) {
            try {
                let value = localStorage.getItem(this.prefix + key);
                return value === null ? null : JSON.parse(value);
            } catch (err) {
                return null;
            }
        },
        remove: function (key) {
            try {
                localStorage.removeItem(this.prefix + key);
            } catch (err) {}
        },
        removeAll: function () {
            try {
                Object.keys(localStorage).forEach(function (key) {
                    if (_.startsWith(key, this.prefix)) localStorage.removeItem(key);
                }, this);
            } catch (err) {}
        },
        vsCurrency: function (id) {
            if (id === undefined) return this.get('vs_currency') || GeckoClient.defaultVsCurrencyId;
            this.set('vs_currency', id);
        },
        theme: function (theme) {
            if (theme === undefined) {
                if (GeckoClient.telegram && GeckoClient.telegram.active && GeckoClient.telegram.colorScheme) {
                    return GeckoClient.telegram.colorScheme;
                }

                return this.get('theme') || (GeckoClient.vuetifyOptions.theme.dark ? 'dark' : 'light');
            }
            if (GeckoClient.telegram && GeckoClient.telegram.active) return;
            this.set('theme', theme);
        },
        cookiesAccepted: function (accepted) {
            if (accepted === undefined) return this.get('cookies_accepted') || 0;
            if (accepted === true) return this.set('cookies_accepted', Date.now())
        }
    };

    GeckoClient.telegram = {
        active: false,
        webApp: null,
        colorScheme: null,
        themeParams: {},
        callbacks: [],
        isTelegramSurface: function () {
            return _.get(GeckoClient, 'runtime.profile') === 'telegram' || !!utils.getTelegramWebApp();
        },
        setColorScheme: function (colorScheme) {
            this.colorScheme = colorScheme === 'dark' ? 'dark' : 'light';
            GeckoClient.setDocumentThemeClass(this.colorScheme);
        },
        applyThemeParams: function (params) {
            const rootStyle = document.documentElement.style;
            this.themeParams = {};

            _.forOwn(params || {}, (value, key) => {
                const color = utils.normalizeHexColor(value);
                if (!color) return;

                this.themeParams[key] = color;
                if (telegramThemeParamMap[key]) {
                    rootStyle.setProperty(telegramThemeParamMap[key], color);
                }
            });

            this.syncNativeColors();
            this.callbacks.forEach(callback => callback(this));
        },
        getVuetifyThemePatch: function () {
            const patch = {};

            _.forOwn(telegramVuetifyParamMap, (vuetifyKey, telegramKey) => {
                const color = this.themeParams[telegramKey];
                if (color) patch[vuetifyKey] = color;
            });

            if (this.themeParams.destructive_text_color) {
                patch.error = this.themeParams.destructive_text_color;
            }

            if (this.themeParams.button_text_color) {
                patch.high_text = this.themeParams.button_text_color;
            }

            return patch;
        },
        syncNativeColors: function () {
            const webApp = this.webApp;
            if (!webApp) return;

            const headerColor = this.themeParams.header_bg_color || this.themeParams.bg_color || this.themeParams.button_color;
            const backgroundColor = this.themeParams.bg_color || this.themeParams.secondary_bg_color;
            const bottomBarColor = this.themeParams.bottom_bar_bg_color || this.themeParams.secondary_bg_color || backgroundColor;

            if (headerColor && _.isFunction(webApp.setHeaderColor)) {
                webApp.setHeaderColor(headerColor);
            }
            if (backgroundColor && _.isFunction(webApp.setBackgroundColor)) {
                webApp.setBackgroundColor(backgroundColor);
            }
            if (bottomBarColor && _.isFunction(webApp.setBottomBarColor)) {
                webApp.setBottomBarColor(bottomBarColor);
            }

            const metaThemeColor = document.querySelector('meta[name="theme-color"]');
            if (metaThemeColor && (headerColor || backgroundColor)) {
                metaThemeColor.content = headerColor || backgroundColor;
            }
        },
        onThemeChange: function (callback) {
            if (_.isFunction(callback)) {
                this.callbacks.push(callback);
            }
        },
        init: function () {
            this.webApp = utils.getTelegramWebApp();
            this.active = this.isTelegramSurface();

            if (!this.active) {
                GeckoClient.setDocumentThemeClass(GeckoClient.preferences.theme());
                return this;
            }

            document.documentElement.classList.add('tbc-telegram-webview');
            this.setColorScheme(_.get(this.webApp, 'colorScheme', 'light'));
            this.applyThemeParams(_.get(this.webApp, 'themeParams', {}));

            if (this.webApp && _.isFunction(this.webApp.onEvent)) {
                this.webApp.onEvent('themeChanged', () => {
                    this.setColorScheme(_.get(this.webApp, 'colorScheme', this.colorScheme));
                    this.applyThemeParams(_.get(this.webApp, 'themeParams', this.themeParams));
                });
            }

            if (this.webApp && _.isFunction(this.webApp.ready)) {
                this.webApp.ready();
            }
            if (this.webApp && _.isFunction(this.webApp.expand)) {
                this.webApp.expand();
            }

            return this;
        }
    };

    GeckoClient.telegram.init();

    const scriptLoadPromises = {};

    GeckoClient.loadScript = function (src) {
        if (!src) return Promise.reject(new Error('Script URL is required'));
        if (scriptLoadPromises[src]) return scriptLoadPromises[src];

        scriptLoadPromises[src] = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => resolve(script);
            script.onerror = () => {
                delete scriptLoadPromises[src];
                reject(new Error('Failed to load script: ' + src));
            };
            document.head.appendChild(script);
        });

        return scriptLoadPromises[src];
    };

    GeckoClient.loadECharts = function () {
        const registerDarkTheme = () => {
            if (_.isFunction(GeckoClient.registerEChartsDarkTheme)) {
                GeckoClient.registerEChartsDarkTheme(window.echarts);
            }
        };

        if (window.echarts) {
            registerDarkTheme();
            return Promise.resolve(window.echarts);
        }

        const echartsUrl = _.get(GeckoClient, 'assets.echartsUrl');
        if (!echartsUrl) {
            return Promise.reject(new Error('ECharts asset URL is not configured'));
        }

        return GeckoClient.loadScript(echartsUrl).then(() => {
            if (!window.echarts) {
                throw new Error('ECharts failed to initialize');
            }
            registerDarkTheme();
            return window.echarts;
        });
    };

    GeckoClient.getOptions = (path, defaultValue) => _.get(GeckoClient.options, path, defaultValue);

    GeckoClient.analytics = {
        events: [],
        allowedEvents: ['search_opened', 'search_result_selected', 'watchlist_added', 'watchlist_removed'],
        allowedProperties: [
            'trigger',
            'query_present',
            'surface',
            'result_type',
            'coin_id',
            'symbol',
            'exchange_id',
            'category_id',
            'rank',
            'query_length_bucket',
            'source_route',
            'storage_mode'
        ],
        newEventId: function () {
            return 'evt_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
        },
        surface: function () {
            if (_.get(GeckoClient, 'runtime.profile') === 'telegram') return 'telegram_mini_app';
            return utils.isMobileUserAgent() ? 'mobile_web' : 'public_web';
        },
        queryLengthBucket: function (query) {
            const length = _.replace(_.trim(_.toLower(query || '')), /\s+/g, '').length;
            if (length === 0) return 'empty';
            if (length <= 2) return '1-2';
            if (length <= 5) return '3-5';
            if (length <= 10) return '6-10';
            return '11-plus';
        },
        sanitizeProperties: function (properties) {
            return _.pick(properties || {}, this.allowedProperties);
        },
        emit: function (eventName, properties) {
            if (this.allowedEvents.indexOf(eventName) === -1) return null;

            const event = Object.assign(
                {
                    event_id: this.newEventId(),
                    event_name: eventName,
                    occurred_at: new Date().toISOString(),
                    surface: this.surface()
                },
                this.sanitizeProperties(properties)
            );

            this.events.push(event);
            if (typeof window.CustomEvent === 'function') {
                window.dispatchEvent(new CustomEvent('tonbankcard:analytics', {detail: event}));
            }
            return event;
        }
    };

    GeckoClient.getVuetifyOptions = () => {
        const options = _.cloneDeep(GeckoClient.vuetifyOptions);
        const theme = GeckoClient.preferences.theme();
        options.theme.dark = theme === 'dark';
        GeckoClient.setDocumentThemeClass(theme);

        if (GeckoClient.telegram.active) {
            options.theme.themes[theme] = Object.assign(
                {},
                options.theme.themes[theme] || {},
                GeckoClient.telegram.getVuetifyThemePatch()
            );
        }

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

        const og = document.querySelector('meta[property="og:url"]')
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
