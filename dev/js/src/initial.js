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

    const pwaConfig = GeckoClient.pwa || {};

    GeckoClient.pwa = {
        serviceWorkerUrl: pwaConfig.serviceWorkerUrl || '/service-worker.js',
        offlineUrl: pwaConfig.offlineUrl || '/offline.html',
        serviceWorkerRegistration: null,
        serviceWorkerError: null,
        installPromptEvent: null,
        installAvailable: false,
        callbacks: [],
        installPromptListening: false,
        registerServiceWorker: function () {
            if (!('serviceWorker' in navigator) || !/^https?:$/.test(window.location.protocol)) {
                return Promise.resolve(null);
            }

            return navigator.serviceWorker.register(this.serviceWorkerUrl)
                .then(registration => {
                    this.serviceWorkerRegistration = registration;
                    return registration;
                })
                .catch(err => {
                    this.serviceWorkerError = err.message || 'service_worker_registration_failed';
                    return null;
                });
        },
        initInstallPrompt: function () {
            if (this.installPromptListening) return;
            this.installPromptListening = true;

            window.addEventListener('beforeinstallprompt', event => {
                if (_.isFunction(event.preventDefault)) {
                    event.preventDefault();
                }
                this.installPromptEvent = event;
                this.installAvailable = true;
                this.notifyInstallChange();
            });

            window.addEventListener('appinstalled', () => {
                this.installPromptEvent = null;
                this.installAvailable = false;
                this.notifyInstallChange();
            });
        },
        notifyInstallChange: function () {
            const available = this.installAvailable;
            this.callbacks.forEach(callback => callback(available));
            if (typeof window.CustomEvent === 'function') {
                window.dispatchEvent(new CustomEvent('tonbankcard:pwa-install-available', {
                    detail: {available: available}
                }));
            }
        },
        onInstallChange: function (callback) {
            if (_.isFunction(callback)) {
                this.callbacks.push(callback);
                callback(this.installAvailable);
            }
        },
        promptInstall: function () {
            const event = this.installPromptEvent;
            if (!event || !_.isFunction(event.prompt)) {
                return Promise.resolve(false);
            }

            this.installPromptEvent = null;
            this.installAvailable = false;
            this.notifyInstallChange();

            let promptResult;
            try {
                promptResult = event.prompt();
            } catch (err) {
                return Promise.resolve(false);
            }

            return Promise.resolve(promptResult)
                .then(() => {
                    if (event.userChoice && _.isFunction(event.userChoice.then)) {
                        return event.userChoice.then(choice => _.get(choice, 'outcome') === 'accepted');
                    }
                    return true;
                })
                .catch(() => false);
        },
        init: function () {
            this.initInstallPrompt();
            this.registerServiceWorker();
            return this;
        }
    };

    GeckoClient.pwa.init();

    function callTelegramMethod(target, method, args) {
        if (!target || !_.isFunction(target[method])) return undefined;

        try {
            return target[method].apply(target, args || []);
        } catch (err) {
            return undefined;
        }
    }

    function createTelegramButtonController(buttonName) {
        return {
            name: buttonName,
            get: function () {
                return _.get(GeckoClient, 'telegram.webApp.' + buttonName) || null;
            },
            show: function () {
                callTelegramMethod(this.get(), 'show');
                return this;
            },
            hide: function () {
                callTelegramMethod(this.get(), 'hide');
                return this;
            },
            enable: function () {
                callTelegramMethod(this.get(), 'enable');
                return this;
            },
            disable: function () {
                callTelegramMethod(this.get(), 'disable');
                return this;
            },
            showProgress: function (leaveActive) {
                callTelegramMethod(this.get(), 'showProgress', [!!leaveActive]);
                return this;
            },
            hideProgress: function () {
                callTelegramMethod(this.get(), 'hideProgress');
                return this;
            },
            setText: function (text) {
                if (_.isString(text) && text.length) {
                    callTelegramMethod(this.get(), 'setText', [text]);
                }
                return this;
            },
            setParams: function (params) {
                callTelegramMethod(this.get(), 'setParams', [params || {}]);
                return this;
            },
            onClick: function (callback) {
                const button = this.get();
                if (!button || !_.isFunction(callback)) return false;

                if (_.isFunction(button.onClick)) {
                    button.onClick(callback);
                    return true;
                }

                return false;
            },
            offClick: function (callback) {
                const button = this.get();
                if (button && _.isFunction(button.offClick) && _.isFunction(callback)) {
                    button.offClick(callback);
                }
                return this;
            }
        };
    }

    GeckoClient.telegram = {
        active: false,
        webApp: null,
        colorScheme: null,
        themeParams: {},
        callbacks: [],
        routeStack: [],
        handlingBackNavigation: false,
        backButtonBound: false,
        isTelegramSurface: function () {
            return _.get(GeckoClient, 'runtime.profile') === 'telegram' || !!utils.getTelegramWebApp();
        },
        callWebApp: function (method, args) {
            return callTelegramMethod(this.webApp, method, args);
        },
        setColorScheme: function (colorScheme) {
            this.colorScheme = colorScheme === 'dark' ? 'dark' : 'light';
            GeckoClient.setDocumentThemeClass(this.colorScheme);
        },
        cssPixel: function (value) {
            const number = parseFloat(value);
            return _.isFinite(number) ? Math.max(0, number) + 'px' : null;
        },
        applySafeAreaInsets: function (inset, prefix) {
            const rootStyle = document.documentElement.style;
            let applied = false;

            ['top', 'right', 'bottom', 'left'].forEach(edge => {
                const value = this.cssPixel(_.get(inset, edge));
                if (!value) return;

                rootStyle.setProperty(prefix + '-' + edge, value);
                applied = true;
            });

            return applied;
        },
        syncViewport: function () {
            const webApp = this.webApp;
            const root = document.documentElement;
            const rootStyle = root.style;

            if (!webApp) return;

            const viewportHeight = this.cssPixel(_.get(webApp, 'viewportHeight'));
            const viewportStableHeight = this.cssPixel(_.get(webApp, 'viewportStableHeight'));

            if (viewportHeight) {
                rootStyle.setProperty('--tbc-viewport-height', viewportHeight);
            }
            if (viewportStableHeight) {
                rootStyle.setProperty('--tbc-viewport-stable-height', viewportStableHeight);
            }

            this.applySafeAreaInsets(_.get(webApp, 'safeAreaInset'), '--tbc-safe-area');
            this.applySafeAreaInsets(_.get(webApp, 'contentSafeAreaInset'), '--tbc-content-safe-area');

            root.classList.toggle('tbc-telegram-expanded', !!_.get(webApp, 'isExpanded'));
            root.classList.toggle('tbc-telegram-fullscreen', !!_.get(webApp, 'isFullscreen'));
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
        onEvent: function (name, callback) {
            if (this.webApp && _.isFunction(this.webApp.onEvent) && _.isFunction(callback)) {
                this.webApp.onEvent(name, callback);
                return true;
            }
            return false;
        },
        ready: function () {
            this.callWebApp('ready');
        },
        expand: function () {
            this.callWebApp('expand');
        },
        requestFullscreen: function () {
            this.callWebApp('requestFullscreen');
        },
        configureMainButton: function (params) {
            this.mainButton.setParams(params).show();
            return this.mainButton;
        },
        configureSecondaryButton: function (params) {
            this.secondaryButton.setParams(params).show();
            return this.secondaryButton;
        },
        getHapticFeedback: function () {
            return _.get(this.webApp, 'HapticFeedback') || null;
        },
        hapticImpact: function (style) {
            callTelegramMethod(this.getHapticFeedback(), 'impactOccurred', [style || 'light']);
        },
        hapticNotification: function (type) {
            callTelegramMethod(this.getHapticFeedback(), 'notificationOccurred', [type || 'success']);
        },
        hapticSelection: function () {
            callTelegramMethod(this.getHapticFeedback(), 'selectionChanged');
        },
        showPopup: function (params, callback) {
            if (this.webApp && _.isFunction(this.webApp.showPopup)) {
                this.webApp.showPopup(params || {}, callback);
                return true;
            }

            if (_.isFunction(callback)) {
                callback(null);
            }
            return false;
        },
        showAlert: function (message, callback) {
            if (this.webApp && _.isFunction(this.webApp.showAlert)) {
                this.webApp.showAlert(message, callback);
                return true;
            }

            if (_.isFunction(window.alert)) {
                window.alert(message);
            }
            if (_.isFunction(callback)) {
                callback();
            }
            return false;
        },
        showConfirm: function (message, callback) {
            if (this.webApp && _.isFunction(this.webApp.showConfirm)) {
                this.webApp.showConfirm(message, callback);
                return true;
            }

            const confirmed = _.isFunction(window.confirm) ? window.confirm(message) : false;
            if (_.isFunction(callback)) {
                callback(confirmed);
            }
            return false;
        },
        shareToStory: function (mediaUrl, params) {
            if (this.webApp && _.isFunction(this.webApp.shareToStory) && mediaUrl) {
                this.webApp.shareToStory(mediaUrl, params || {});
                return true;
            }
            return false;
        },
        switchInlineQuery: function (query, chatTypes) {
            if (this.webApp && _.isFunction(this.webApp.switchInlineQuery)) {
                this.webApp.switchInlineQuery(query || '', chatTypes || []);
                return true;
            }
            return false;
        },
        shareUrl: function (url, text) {
            const shareUrl = utils.validURLString(url, window.location.href);
            if (!shareUrl) return Promise.resolve(false);

            if (this.webApp && _.isFunction(this.webApp.openTelegramLink)) {
                const telegramShareUrl = 'https://t.me/share/url?url=' + encodeURIComponent(shareUrl)
                    + (text ? '&text=' + encodeURIComponent(text) : '');
                this.webApp.openTelegramLink(telegramShareUrl);
                return Promise.resolve(true);
            }

            if (_.isFunction(navigator.share)) {
                return navigator.share({url: shareUrl, text: text || document.title}).then(() => true).catch(() => false);
            }

            if (_.get(navigator, 'clipboard.writeText')) {
                return navigator.clipboard.writeText(shareUrl).then(() => true).catch(() => false);
            }

            return Promise.resolve(false);
        },
        bindBackButton: function () {
            if (this.backButtonBound) return;
            this.backButtonBound = this.backButton.onClick(() => this.goBack());
        },
        updateBackButton: function (to, from) {
            if (!this.active) return false;

            const toPath = _.get(to, 'fullPath') || _.get(to, 'path') || window.location.pathname;
            const fromPath = _.get(from, 'fullPath') || _.get(from, 'path') || '';

            if (!this.handlingBackNavigation && fromPath && fromPath !== toPath) {
                this.routeStack.push(fromPath);
                if (this.routeStack.length > 20) {
                    this.routeStack.shift();
                }
            }
            this.handlingBackNavigation = false;

            const shouldShow = this.routeStack.length > 0 || (toPath && toPath !== '/');
            if (shouldShow) {
                this.backButton.show();
            } else {
                this.backButton.hide();
            }
            return shouldShow;
        },
        goBack: function () {
            const router = GeckoClient.router;
            const target = this.routeStack.pop();
            this.handlingBackNavigation = true;

            if (router && target) {
                router.push(target).catch(() => {});
                this.hapticSelection();
                return true;
            }

            if (router && _.get(router, 'currentRoute.path') !== '/') {
                router.push('/').catch(() => {});
                this.hapticSelection();
                return true;
            }

            this.handlingBackNavigation = false;
            this.backButton.hide();
            return false;
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
            this.syncViewport();
            this.bindBackButton();

            this.onEvent('themeChanged', () => {
                this.setColorScheme(_.get(this.webApp, 'colorScheme', this.colorScheme));
                this.applyThemeParams(_.get(this.webApp, 'themeParams', this.themeParams));
            });
            this.onEvent('viewportChanged', () => this.syncViewport());
            this.onEvent('safeAreaChanged', () => this.syncViewport());
            this.onEvent('contentSafeAreaChanged', () => this.syncViewport());

            this.ready();
            this.expand();
            this.requestFullscreen();

            return this;
        }
    };
    GeckoClient.telegram.backButton = createTelegramButtonController('BackButton');
    GeckoClient.telegram.mainButton = createTelegramButtonController('MainButton');
    GeckoClient.telegram.secondaryButton = createTelegramButtonController('SecondaryButton');

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
        allowedEvents: [
            'search_opened',
            'search_result_selected',
            'watchlist_added',
            'watchlist_removed',
            'alert_created',
            'alert_updated',
            'alert_paused',
            'alert_deleted',
            'alert_tested'
        ],
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
            'storage_mode',
            'trigger_type',
            'delivery_channel',
            'threshold_bucket',
            'quiet_hours_enabled',
            'alert_id',
            'status'
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
