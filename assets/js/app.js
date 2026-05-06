(function (window) {
    'use strict';

    /*
     * Licensed to the Apache Software Foundation (ASF) under one
     * or more contributor license agreements.  See the NOTICE file
     * distributed with this work for additional information
     * regarding copyright ownership.  The ASF licenses this file
     * to you under the Apache License, Version 2.0 (the
     * "License"); you may not use this file except in compliance
     * with the License.  You may obtain a copy of the License at
     *
     *   http://www.apache.org/licenses/LICENSE-2.0
     *
     * Unless required by applicable law or agreed to in writing,
     * software distributed under the License is distributed on an
     * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
     * KIND, either express or implied.  See the License for the
     * specific language governing permissions and limitations
     * under the License.
     */

    (function(root, factory) {
        function registerDarkTheme(echartsInstance) {
            factory({}, echartsInstance);
            return echartsInstance;
        }

        root.GeckoClient = root.GeckoClient || {};
        root.GeckoClient.registerEChartsDarkTheme = registerDarkTheme;

        if (typeof define === 'function' && define.amd) {
            // AMD. Register as an anonymous module.
            define(['exports', 'echarts'], function(exports, echarts) {
                factory(exports, echarts);
            });
        } else if (
            typeof exports === 'object' &&
            typeof exports.nodeName !== 'string'
        ) {
            // CommonJS
            factory(exports, require('echarts/lib/echarts'));
        } else if (root.echarts) {
            // Browser globals
            registerDarkTheme(root.echarts);
        }
    })(window, function(exports, echarts) {
        let log = function(msg) {
            if (typeof console !== 'undefined') {
                console && console.error && console.error(msg);
            }
        };
        if (!echarts) {
            log('ECharts is not loaded');
            return;
        }
        if (echarts.__tbcDarkThemeRegistered) {
            return;
        }
        let contrastColor = '#B9B8CE';
        let backgroundColor = 'transparent';
        let axisCommon = function () {
            return {
                axisLine: {
                    lineStyle: {
                        color: contrastColor
                    }
                },
                splitLine: {
                    lineStyle: {
                        color: '#484753'
                    }
                },
                splitArea: {
                    areaStyle: {
                        color: ['rgba(255,255,255,0.02)', 'rgba(255,255,255,0.05)']
                    }
                },
                minorSplitLine: {
                    lineStyle: {
                        color: '#20203B'
                    }
                }
            };
        };

        let colorPalette = [
            '#4992ff',
            '#7cffb2',
            '#fddd60',
            '#ff6e76',
            '#58d9f9',
            '#05c091',
            '#ff8a45',
            '#8d48e3',
            '#dd79ff'
        ];
        let theme = {
            darkMode: true,

            color: colorPalette,
            backgroundColor: backgroundColor,
            axisPointer: {
                lineStyle: {
                    color: '#817f91'
                },
                crossStyle: {
                    color: '#817f91'
                },
                label: {
                    // TODO Contrast of label backgorundColor
                    color: '#fff'
                }
            },
            legend: {
                textStyle: {
                    color: contrastColor
                }
            },
            textStyle: {
                color: contrastColor
            },
            title: {
                textStyle: {
                    color: '#EEF1FA'
                },
                subtextStyle: {
                    color: '#B9B8CE'
                }
            },
            toolbox: {
                iconStyle: {
                    borderColor: contrastColor
                }
            },
            dataZoom: {
                borderColor: '#71708A',
                textStyle: {
                    color: contrastColor
                },
                brushStyle: {
                    color: 'rgba(135,163,206,0.3)'
                },
                handleStyle: {
                    color: '#353450',
                    borderColor: '#C5CBE3'
                },
                moveHandleStyle: {
                    color: '#B0B6C3',
                    opacity: 0.3
                },
                fillerColor: 'rgba(135,163,206,0.2)',
                emphasis: {
                    handleStyle: {
                        borderColor: '#91B7F2',
                        color: '#4D587D'
                    },
                    moveHandleStyle: {
                        color: '#636D9A',
                        opacity: 0.7
                    }
                },
                dataBackground: {
                    lineStyle: {
                        color: '#71708A',
                        width: 1
                    },
                    areaStyle: {
                        color: '#71708A'
                    }
                },
                selectedDataBackground: {
                    lineStyle: {
                        color: '#87A3CE'
                    },
                    areaStyle: {
                        color: '#87A3CE'
                    }
                }
            },
            visualMap: {
                textStyle: {
                    color: contrastColor
                }
            },
            timeline: {
                lineStyle: {
                    color: contrastColor
                },
                label: {
                    color: contrastColor
                },
                controlStyle: {
                    color: contrastColor,
                    borderColor: contrastColor
                }
            },
            calendar: {
                itemStyle: {
                    color: backgroundColor
                },
                dayLabel: {
                    color: contrastColor
                },
                monthLabel: {
                    color: contrastColor
                },
                yearLabel: {
                    color: contrastColor
                }
            },
            timeAxis: axisCommon(),
            logAxis: axisCommon(),
            valueAxis: axisCommon(),
            categoryAxis: axisCommon(),

            line: {
                symbol: 'circle'
            },
            graph: {
                color: colorPalette
            },
            gauge: {
                title: {
                    color: contrastColor
                }
            },
            candlestick: {
                itemStyle: {
                    color: '#FD1050',
                    color0: '#0CF49B',
                    borderColor: '#FD1050',
                    borderColor0: '#0CF49B'
                }
            }
        };

        theme.categoryAxis.splitLine.show = false;
        echarts.registerTheme('dark', theme);
        echarts.__tbcDarkThemeRegistered = true;
    });
})(window);

(function (window, navigator, _, Vue, axios, GeckoClient) {
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

    GeckoClient.security = (function () {
        const existing = GeckoClient.security || {};
        const unsafeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        const headerName = existing.csrfHeaderName || 'X-TONBANKCARD-CSRF';
        let csrfToken = normalizeToken(existing.csrfToken || '');

        function normalizeToken(value) {
            value = _.toLower(_.trim(value || ''));
            return /^[a-f0-9]{64}$/.test(value) ? value : '';
        }

        function isSameOriginApi(url) {
            if (!url) return false;

            try {
                const parsed = new URL(url, window.location.href);
                return parsed.origin === window.location.origin && /^\/api(?:\/|$)/.test(parsed.pathname);
            } catch (err) {
                return _.startsWith(url, '/api/');
            }
        }

        function shouldAttach(config) {
            const method = _.toUpper(_.get(config, 'method', 'GET'));
            return !!csrfToken
                && unsafeMethods.indexOf(method) >= 0
                && _.get(config, 'withCredentials') === true
                && isSameOriginApi(_.get(config, 'url', ''));
        }

        function attach(config) {
            if (!shouldAttach(config)) return config;

            config.headers = Object.assign({}, config.headers || {});
            config.headers[headerName] = csrfToken;
            return config;
        }

        function captureSessionResponse(response) {
            const token = normalizeToken(_.get(response, 'data.data.session.csrf_token'));
            if (token) csrfToken = token;
            return response;
        }

        function installAxiosInterceptors() {
            if (!axios || !axios.interceptors || axios.__tonbankcardSecurityInterceptors) return;

            axios.interceptors.request.use(config => attach(config || {}));
            axios.interceptors.response.use(response => captureSessionResponse(response));
            axios.__tonbankcardSecurityInterceptors = true;
        }

        installAxiosInterceptors();

        return {
            csrfHeaderName: headerName,
            captureSessionResponse: captureSessionResponse,
            csrfHeaders: function () {
                return csrfToken ? {[headerName]: csrfToken} : {};
            },
            csrfToken: function (value) {
                if (value === undefined) return csrfToken;
                csrfToken = normalizeToken(value);
                return csrfToken;
            }
        };
    })();

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
            'alert_tested',
            'share_started',
            'referral_opened',
            'achievement_opted_in',
            'achievement_opted_out',
            'achievement_streak_updated',
            'achievement_prompted',
            'achievement_unlocked',
            'achievement_dismissed',
            'achievement_shared'
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
            'status',
            'share_context',
            'campaign',
            'route',
            'share_target',
            'achievement_id',
            'achievement_category',
            'achievement_count',
            'streak_count',
            'streak_timezone',
            'prompt_state',
            'haptic_type',
            'movement_bucket'
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


})(window, navigator, _, Vue, axios, GeckoClient);

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

(function (window, _, GeckoClient) {
    'use strict';

    const config = GeckoClient.achievementsConfig || {};
    const settings = normalizeSettings(_.get(config, 'settings', {}));
    const definitions = normalizeDefinitions(_.get(config, 'definitions', fallbackDefinitions(settings)));
    const definitionMap = _.keyBy(definitions, 'id');
    const changedEventName = 'tonbankcard:achievements-changed';
    let initialized = false;
    let state = emptyState();
    const listeners = [];

    function fallbackDefinitions(sourceSettings) {
        return [
            {id: 'first_watchlist', title: 'First watchlist', description: 'Save the first market asset to a watchlist.', trigger: 'watchlist_added', threshold: 1, category: 'useful_setup', badge_icon: 'mdi-star-check-outline'},
            {id: 'first_alert', title: 'First alert', description: 'Create the first smart alert rule.', trigger: 'alert_created', threshold: 1, category: 'useful_setup', badge_icon: 'mdi-bell-check-outline'},
            {id: 'weekly_market_check', title: 'Weekly market check', description: 'Check market pulse on consecutive local days.', trigger: 'market_check', threshold: sourceSettings.weekly_check_days, category: 'streak', badge_icon: 'mdi-calendar-week-outline'},
            {id: 'ton_explorer', title: 'TON explorer', description: 'Open TON ecosystem market context.', trigger: 'ton_viewed', threshold: 1, category: 'ton_ecosystem', badge_icon: 'mdi-diamond-stone'},
            {id: 'share_milestone', title: 'Share milestone', description: 'Share useful market context.', trigger: 'share_started', threshold: sourceSettings.share_milestone_count, category: 'sharing', badge_icon: 'mdi-share-variant-outline'},
            {id: 'caught_market_movement', title: 'Caught market movement', description: 'Open a market view with a qualifying 24h move.', trigger: 'market_movement_caught', threshold: sourceSettings.movement_threshold_percent, category: 'market_awareness', badge_icon: 'mdi-trending-up'}
        ];
    }

    function normalizeSettings(source) {
        source = source || {};
        return {
            enabled: source.enabled === true,
            definitions_version: _.toString(source.definitions_version || 'v1'),
            storage_key: _.toString(source.storage_key || 'TONBANKCARD:achievements:v1'),
            weekly_check_days: clampInt(source.weekly_check_days, 7, 2, 30),
            share_milestone_count: clampInt(source.share_milestone_count, 3, 1, 100),
            movement_threshold_percent: clampNumber(source.movement_threshold_percent, 7.5, 1, 100),
            max_prompts_per_session: clampInt(source.max_prompts_per_session, 1, 1, 6),
            prompt_cooldown_hours: clampInt(source.prompt_cooldown_hours, 24, 1, 168),
            haptics_enabled: source.haptics_enabled !== false
        };
    }

    function normalizeDefinitions(items) {
        return (items || []).map(item => ({
            id: _.toString(item.id || '').trim(),
            title: _.toString(item.title || '').trim(),
            description: _.toString(item.description || '').trim(),
            trigger: _.toString(item.trigger || '').trim(),
            threshold: _.isNumber(item.threshold) ? item.threshold : parseFloat(item.threshold) || 1,
            category: _.toString(item.category || 'progress').trim(),
            badge_icon: _.toString(item.badge_icon || 'mdi-trophy-outline').trim()
        })).filter(item => item.id && item.title);
    }

    function clampInt(value, fallback, min, max) {
        value = parseInt(value, 10);
        if (!_.isFinite(value)) value = fallback;
        return Math.max(min, Math.min(max, value));
    }

    function clampNumber(value, fallback, min, max) {
        value = parseFloat(value);
        if (!_.isFinite(value)) value = fallback;
        return Math.max(min, Math.min(max, value));
    }

    function canUseLocalStorage() {
        try {
            const key = '__tonbankcard_achievements_test__';
            window.localStorage.setItem(key, '1');
            window.localStorage.removeItem(key);
            return true;
        } catch (err) {
            return false;
        }
    }

    function nowIso() {
        return new Date().toISOString();
    }

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function userTimezone() {
        return _.get(Intl.DateTimeFormat().resolvedOptions(), 'timeZone') || 'UTC';
    }

    function emptyState() {
        return {
            version: 1,
            opted_in: false,
            achievements: {},
            dismissed: {},
            counters: {},
            market_check_dates: [],
            streak: {
                timezone: userTimezone(),
                current_count: 0,
                max_count: 0,
                last_local_date: null
            },
            events: [],
            updated_at: null
        };
    }

    function normalizeState(raw) {
        const normalized = Object.assign(emptyState(), _.isPlainObject(raw) ? raw : {});
        normalized.achievements = _.isPlainObject(normalized.achievements) ? normalized.achievements : {};
        normalized.dismissed = _.isPlainObject(normalized.dismissed) ? normalized.dismissed : {};
        normalized.counters = _.isPlainObject(normalized.counters) ? normalized.counters : {};
        normalized.market_check_dates = _.isArray(normalized.market_check_dates) ? normalized.market_check_dates : [];
        normalized.events = _.isArray(normalized.events) ? normalized.events : [];
        normalized.streak = Object.assign(emptyState().streak, _.isPlainObject(normalized.streak) ? normalized.streak : {});
        return normalized;
    }

    function loadStoredState() {
        if (!canUseLocalStorage()) return emptyState();

        try {
            const raw = window.localStorage.getItem(settings.storage_key);
            return normalizeState(raw ? JSON.parse(raw) : {});
        } catch (err) {
            return emptyState();
        }
    }

    function persistState() {
        state.updated_at = nowIso();
        if (!canUseLocalStorage()) return;

        try {
            window.localStorage.setItem(settings.storage_key, JSON.stringify(state));
        } catch (err) {}
    }

    function ensureInit() {
        if (initialized) return;
        state = loadStoredState();
        initialized = true;
    }

    function init() {
        ensureInit();
        notify();
        return Promise.resolve(GeckoClient.achievements);
    }

    function snapshot() {
        ensureInit();
        return clone({
            version: state.version,
            opted_in: state.opted_in,
            feature_enabled: settings.enabled,
            settings: settings,
            definitions: definitions,
            achievements: state.achievements,
            dismissed: state.dismissed,
            counters: state.counters,
            market_check_dates: state.market_check_dates,
            streak: state.streak,
            events: state.events,
            unlocked_count: unlockedCount(),
            updated_at: state.updated_at
        });
    }

    function notify() {
        const detail = snapshot();
        listeners.slice().forEach(callback => callback(detail));

        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent(changedEventName, {detail: detail}));
        }
    }

    function onChange(callback) {
        if (!_.isFunction(callback)) return function () {};
        listeners.push(callback);
        callback(snapshot());

        return function () {
            const index = listeners.indexOf(callback);
            if (index >= 0) listeners.splice(index, 1);
        };
    }

    function emitAnalytics(eventName, properties) {
        if (!GeckoClient.analytics) return null;
        return GeckoClient.analytics.emit(eventName, properties || {});
    }

    function hapticNotification(type) {
        if (!settings.haptics_enabled || !GeckoClient.telegram) return;
        if (_.isFunction(GeckoClient.telegram.hapticNotification)) {
            GeckoClient.telegram.hapticNotification(type || 'success');
        }
    }

    function enable() {
        ensureInit();
        if (state.opted_in) return snapshot();

        state.opted_in = true;
        persistState();
        emitAnalytics('achievement_opted_in', {prompt_state: 'enabled'});
        notify();
        return snapshot();
    }

    function disable() {
        ensureInit();
        if (!state.opted_in) return snapshot();

        state.opted_in = false;
        persistState();
        emitAnalytics('achievement_opted_out', {prompt_state: 'disabled'});
        notify();
        return snapshot();
    }

    function canTrack() {
        ensureInit();
        return settings.enabled === true && state.opted_in === true;
    }

    function incrementCounter(name, amount) {
        state.counters[name] = (parseInt(state.counters[name], 10) || 0) + (amount || 1);
        return state.counters[name];
    }

    function recordEvent(eventName, properties) {
        state.events.unshift({
            event_name: eventName,
            occurred_at: _.toString(properties.occurred_at || nowIso()),
            coin_id: safeString(properties.coin_id, 96),
            symbol: safeString(properties.symbol, 32),
            source_route: safeString(properties.source_route || properties.sourceRoute, 80)
        });
        state.events = state.events.slice(0, 80);
    }

    function track(eventName, properties) {
        properties = properties || {};
        ensureInit();
        if (!canTrack()) return [];

        const occurredAt = properties.occurred_at || nowIso();
        recordEvent(eventName, Object.assign({}, properties, {occurred_at: occurredAt}));

        if (eventName === 'market_check') {
            updateStreak(occurredAt, properties.timezone || userTimezone());
            emitAnalytics('achievement_streak_updated', {
                streak_count: state.streak.current_count,
                streak_timezone: state.streak.timezone,
                source_route: properties.source_route || properties.sourceRoute || null
            });
        } else {
            incrementCounter(eventName, 1);
        }

        if (eventName === 'market_movement_caught' && !state.counters.market_movement_caught) {
            state.counters.market_movement_caught = 1;
        }

        const unlocked = evaluateAchievements(eventName, properties);
        persistState();
        notify();
        return unlocked;
    }

    function evaluateAchievements(eventName, properties) {
        return definitions
            .map(definition => {
                if (state.achievements[definition.id]) return null;
                if (!achievementSatisfied(definition)) return null;
                return unlockAchievement(definition, eventName, properties);
            })
            .filter(Boolean);
    }

    function achievementSatisfied(definition) {
        if (definition.id === 'weekly_market_check') {
            return (parseInt(state.streak.current_count, 10) || 0) >= settings.weekly_check_days;
        }

        if (definition.id === 'caught_market_movement') {
            return (parseInt(state.counters.market_movement_caught, 10) || 0) >= 1;
        }

        return (parseInt(state.counters[definition.trigger], 10) || 0) >= (parseFloat(definition.threshold) || 1);
    }

    function unlockAchievement(definition, eventName, properties) {
        const activePromptCount = activePrompts().length;
        const promptState = activePromptCount < settings.max_prompts_per_session ? 'active' : 'queued';
        const achievement = {
            id: definition.id,
            unlocked_at: nowIso(),
            event_name: eventName,
            category: definition.category,
            prompt_state: promptState,
            dismissed_at: null,
            shared_at: null
        };

        state.achievements[definition.id] = achievement;
        emitAnalytics('achievement_unlocked', {
            achievement_id: definition.id,
            achievement_category: definition.category,
            achievement_count: unlockedCount(),
            streak_count: state.streak.current_count,
            streak_timezone: state.streak.timezone,
            prompt_state: promptState,
            haptic_type: settings.haptics_enabled ? 'success' : 'off',
            movement_bucket: properties.movement_bucket || null,
            source_route: properties.source_route || properties.sourceRoute || null,
            coin_id: properties.coin_id || null,
            symbol: properties.symbol || null
        });

        if (promptState === 'active') {
            emitPrompted(definition);
            hapticNotification('success');
        }

        return achievement;
    }

    function emitPrompted(definition) {
        emitAnalytics('achievement_prompted', {
            achievement_id: definition.id,
            achievement_category: definition.category,
            achievement_count: unlockedCount(),
            streak_count: state.streak.current_count,
            streak_timezone: state.streak.timezone,
            prompt_state: 'active'
        });
    }

    function activePrompts() {
        return _.values(state.achievements).filter(achievement => achievement.prompt_state === 'active');
    }

    function promoteQueuedPrompt() {
        if (activePrompts().length >= settings.max_prompts_per_session) return;
        const queued = _.find(_.values(state.achievements), achievement => achievement.prompt_state === 'queued');
        if (!queued) return;

        queued.prompt_state = 'active';
        const definition = definitionMap[queued.id];
        if (definition) {
            emitPrompted(definition);
            hapticNotification('success');
        }
    }

    function dismiss(id) {
        ensureInit();
        const achievement = state.achievements[id];
        if (!achievement) return snapshot();

        achievement.prompt_state = 'dismissed';
        achievement.dismissed_at = nowIso();
        state.dismissed[id] = achievement.dismissed_at;
        const definition = definitionMap[id] || {id: id, category: 'progress'};
        emitAnalytics('achievement_dismissed', {
            achievement_id: id,
            achievement_category: definition.category,
            achievement_count: unlockedCount(),
            streak_count: state.streak.current_count,
            streak_timezone: state.streak.timezone,
            prompt_state: 'dismissed'
        });
        promoteQueuedPrompt();
        persistState();
        notify();
        return snapshot();
    }

    function markShared(id) {
        ensureInit();
        const achievement = state.achievements[id];
        if (!achievement) return snapshot();

        achievement.shared_at = nowIso();
        const definition = definitionMap[id] || {id: id, category: 'progress'};
        emitAnalytics('achievement_shared', {
            achievement_id: id,
            achievement_category: definition.category,
            achievement_count: unlockedCount(),
            streak_count: state.streak.current_count,
            streak_timezone: state.streak.timezone,
            share_context: 'achievement_card',
            campaign: 'achievement-' + id.replace(/_/g, '-'),
            route: '/achievements'
        });
        persistState();
        notify();
        return snapshot();
    }

    function localDate(value, timezone) {
        const date = new Date(value);
        if (!GeckoClient.utils.isValidDate(date)) return null;

        try {
            const parts = new Intl.DateTimeFormat('en-US', {
                timeZone: timezone || userTimezone(),
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).formatToParts(date).reduce((memo, part) => {
                memo[part.type] = part.value;
                return memo;
            }, {});

            return parts.year + '-' + parts.month + '-' + parts.day;
        } catch (err) {
            return date.toISOString().slice(0, 10);
        }
    }

    function updateStreak(timestamp, timezone) {
        timezone = timezone || userTimezone();
        const date = localDate(timestamp, timezone);
        if (!date) return state.streak;

        state.streak.timezone = timezone;
        if (state.market_check_dates.indexOf(date) === -1) {
            state.market_check_dates.push(date);
        }
        state.market_check_dates = _.uniq(state.market_check_dates).sort();
        state.streak = calculateStreak(state.market_check_dates, timezone);
        return state.streak;
    }

    function calculateStreak(dates, timezone) {
        dates = _.uniq((dates || []).filter(Boolean)).sort();
        let current = 0;
        let max = 0;
        let previous = null;

        dates.forEach(date => {
            if (!previous || dayDiff(previous, date) !== 1) current = 1;
            else current += 1;
            if (current > max) max = current;
            previous = date;
        });

        return {
            timezone: timezone || userTimezone(),
            current_count: dates.length ? current : 0,
            max_count: max,
            last_local_date: dates.length ? dates[dates.length - 1] : null
        };
    }

    function dayDiff(a, b) {
        const aParts = a.split('-').map(Number);
        const bParts = b.split('-').map(Number);
        const aTime = Date.UTC(aParts[0], aParts[1] - 1, aParts[2]);
        const bTime = Date.UTC(bParts[0], bParts[1] - 1, bParts[2]);
        return Math.round((bTime - aTime) / 86400000);
    }

    function trackRoute(to) {
        const name = _.get(to, 'name');
        const sourceRoute = name || _.get(to, 'path') || 'unknown';
        const paramId = _.toLower(_.get(to, 'params.id'));
        if ((name === 'currency' || name === 'coins') && (paramId === 'toncoin' || paramId === 'the-open-network')) {
            track('ton_viewed', {source_route: sourceRoute, coin_id: paramId, symbol: 'TON'});
        }
    }

    function trackMarketMovement(currency, sourceRoute) {
        const change = parseFloat(_.get(currency, 'price_change_percentage_24h_in_currency', _.get(currency, 'market.price_change_percentage_24h_in_currency')));
        if (!_.isFinite(change) || Math.abs(change) < settings.movement_threshold_percent) return [];

        return track('market_movement_caught', {
            coin_id: _.get(currency, 'id') || _.get(currency, 'coin_id') || null,
            symbol: _.get(currency, 'symbol') || null,
            source_route: sourceRoute || 'market_view',
            movement_bucket: movementBucket(change)
        });
    }

    function movementBucket(change) {
        const absolute = Math.abs(parseFloat(change) || 0);
        if (absolute < 5) return '1_5';
        if (absolute < 10) return '5_10';
        if (absolute < 25) return '10_25';
        return '25_plus';
    }

    function achievementShareCard(id) {
        ensureInit();
        const definition = definitionMap[id];
        if (!definition) return null;

        const achievement = state.achievements[id] || null;
        return {
            title: definition.title + ' badge',
            subtitle: achievement ? 'Unlocked achievement' : 'Achievement progress',
            body: definition.description,
            route: '/achievements',
            campaign: 'achievement-' + id.replace(/_/g, '-'),
            context: 'achievement_card',
            freshness: achievement ? 'Unlocked ' + localDate(achievement.unlocked_at, state.streak.timezone) : 'Progress saved locally',
            metrics: [
                {label: 'Badge', value: definition.title},
                {label: 'Category', value: _.startCase(definition.category)},
                {label: 'Streak', value: String(state.streak.current_count || 0)},
                {label: 'Unlocked', value: String(unlockedCount())}
            ]
        };
    }

    function progressShareCard() {
        ensureInit();
        return {
            title: 'Achievement progress',
            subtitle: unlockedCount() + ' of ' + definitions.length + ' badges',
            body: 'Opt-in TONBANKCARD market progress with streaks, useful setup badges, and shareable milestones.',
            route: '/achievements',
            campaign: 'achievement-progress',
            context: 'achievement_card',
            freshness: state.updated_at ? 'Updated ' + localDate(state.updated_at, state.streak.timezone) : 'Local progress',
            metrics: [
                {label: 'Unlocked', value: String(unlockedCount())},
                {label: 'Remaining', value: String(Math.max(0, definitions.length - unlockedCount()))},
                {label: 'Streak', value: String(state.streak.current_count || 0)},
                {label: 'Timezone', value: state.streak.timezone || userTimezone()}
            ]
        };
    }

    function unlockedCount() {
        return _.keys(state.achievements).length;
    }

    function safeString(value, maxLength) {
        value = _.toString(value || '').trim();
        return value ? value.slice(0, maxLength || 80) : null;
    }

    GeckoClient.achievements = {
        init: init,
        snapshot: snapshot,
        onChange: onChange,
        enable: enable,
        disable: disable,
        dismiss: dismiss,
        markShared: markShared,
        track: track,
        trackRoute: trackRoute,
        trackMarketMovement: trackMarketMovement,
        calculateStreak: calculateStreak,
        achievementShareCard: achievementShareCard,
        progressShareCard: progressShareCard
    };

})(window, _, GeckoClient);

(function (window, navigator, document, _, axios, Vue, GeckoClient) {
    'use strict';

    const runtime = GeckoClient.runtime || {};
    const config = runtime.observability || {};
    const enabled = config.clientErrorReporting !== false;
    const endpoint = config.clientErrorEndpoint || '/api/observability/client-error';
    const idPattern = /^[A-Za-z0-9._:-]{1,128}$/;

    function safeIdentifier(value) {
        value = _.isString(value) ? value.trim() : '';
        return idPattern.test(value) ? value : null;
    }

    function newId(prefix) {
        let suffix = '';
        if (window.crypto && window.crypto.getRandomValues) {
            const bytes = new Uint8Array(8);
            window.crypto.getRandomValues(bytes);
            suffix = Array.prototype.map.call(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
        } else {
            suffix = Math.random().toString(16).slice(2) + Date.now().toString(16);
        }
        return prefix + '-' + suffix;
    }

    function redact(value) {
        value = _.isString(value) ? value : String(value || '');
        return value
            .replace(/(api[_-]?key|x[_-]cg[_-](demo|pro)[_-]api[_-]key|token|password|secret|authorization)=([^&\s]+)/gi, '$1=[redacted]')
            .replace(/Bearer\s+[A-Za-z0-9._~+/=-]+/gi, 'Bearer [redacted]');
    }

    function safeText(value, max) {
        value = redact(value).trim();
        max = max || 300;
        return value.length > max ? value.slice(0, max) + '...' : value;
    }

    function safePath(value) {
        if (!value) return null;
        try {
            const url = new URL(value, window.location.href);
            return url.pathname;
        } catch (err) {
            return _.isString(value) && value.charAt(0) === '/' ? value.split('?')[0] : null;
        }
    }

    function requestUrl(config) {
        config = config || {};
        try {
            return (new URL(config.url || '', config.baseURL || window.location.href)).toString();
        } catch (err) {
            return config.url || '';
        }
    }

    function responseRequestId(error, fallback) {
        const response = error && error.response ? error.response : {};
        const headers = response.headers || {};
        const payload = response.data || {};
        return safeIdentifier(headers['x-request-id'])
            || safeIdentifier(headers['X-Request-ID'])
            || safeIdentifier(_.get(payload, 'meta.request_id'))
            || safeIdentifier(fallback)
            || newId('web-api');
    }

    function send(payload) {
        if (!enabled) return;

        const body = JSON.stringify(payload);
        if (navigator.sendBeacon) {
            try {
                if (navigator.sendBeacon(endpoint, new Blob([body], {type: 'application/json'}))) return;
            } catch (err) {
                // Fall through to fetch.
            }
        }

        if (window.fetch) {
            window.fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Request-ID': newId('web-ingest')},
                body: body,
                credentials: 'same-origin',
                keepalive: true
            }).catch(() => {});
        }
    }

    function report(type, detail) {
        detail = detail || {};
        send({
            type: type,
            client_event_id: safeIdentifier(detail.client_event_id) || newId('web-event'),
            request_id: safeIdentifier(detail.request_id),
            message: safeText(detail.message || type),
            source: safePath(detail.source),
            url_path: safePath(detail.url_path || window.location.href),
            route_path: safePath(detail.route_path || (window.location.pathname || '/')),
            api_path: safePath(detail.api_path),
            method: detail.method,
            status: detail.status,
            error_code: detail.error_code,
            duration_ms: detail.duration_ms,
            line: detail.line,
            column: detail.column,
            component: safeText(detail.component || '', 120)
        });
    }

    function reportApiError(error) {
        const requestConfig = error && error.config ? error.config : {};
        const metadata = requestConfig.tonbankcardObservability || {};
        const response = error && error.response ? error.response : {};
        const payload = response.data || {};
        const requestId = responseRequestId(error, metadata.requestId);
        const startedAt = metadata.startedAt || Date.now();

        report('api_error', {
            request_id: requestId,
            message: error && error.message ? error.message : 'API request failed',
            api_path: requestUrl(requestConfig),
            method: requestConfig.method || metadata.method,
            status: response.status || 0,
            error_code: _.get(payload, 'error.code', error && error.code ? error.code : 'request_failed'),
            duration_ms: Math.max(0, Date.now() - startedAt)
        });
    }

    const observability = GeckoClient.observability = {
        nextRequestId: function (scope) {
            return newId(scope || 'web');
        },
        report: report,
        reportApiError: reportApiError,
        instrumentAxiosInstance: function (client) {
            if (!client || !client.interceptors) return client;

            client.interceptors.request.use(function (requestConfig) {
                requestConfig.headers = requestConfig.headers || {};
                const requestId = safeIdentifier(requestConfig.headers['X-Request-ID'])
                    || safeIdentifier(requestConfig.headers['x-request-id'])
                    || observability.nextRequestId('web-api');
                requestConfig.headers['X-Request-ID'] = requestId;
                requestConfig.tonbankcardObservability = {
                    requestId: requestId,
                    startedAt: Date.now(),
                    method: requestConfig.method || 'get'
                };
                return requestConfig;
            });

            client.interceptors.response.use(
                function (response) {
                    return response;
                },
                function (error) {
                    reportApiError(error);
                    return Promise.reject(error);
                }
            );

            return client;
        }
    };

    window.addEventListener('error', function (event) {
        report('boot_error', {
            message: event && event.message ? event.message : 'Frontend boot error',
            source: event && event.filename ? event.filename : window.location.href,
            line: event ? event.lineno : null,
            column: event ? event.colno : null
        });
    });

    window.addEventListener('unhandledrejection', function (event) {
        const reason = event && event.reason ? event.reason : {};
        report('unhandled_rejection', {
            message: reason && reason.message ? reason.message : 'Unhandled frontend promise rejection',
            source: window.location.href
        });
    });

    if (Vue && Vue.config) {
        const previousErrorHandler = Vue.config.errorHandler;
        Vue.config.errorHandler = function (err, vm, info) {
            report('vue_error', {
                message: err && err.message ? err.message : 'Vue render error',
                source: window.location.href,
                component: info || ''
            });

            if (_.isFunction(previousErrorHandler)) {
                previousErrorHandler.call(this, err, vm, info);
            } else if (err) {
                throw err;
            }
        };
    }

})(window, navigator, document, _, axios, Vue, GeckoClient);

(function (window, _, axios, GeckoClient) {
    'use strict';

    const __ = GeckoClient.__;
    const cg = GeckoClient.cg;
    const utils = GeckoClient.utils;
    const validURLString = utils.validURLString;
    const bitcointalkThreadUrl = utils.bitcointalkThreadUrl;
    const getCustomLink = GeckoClient.getCustomLink;
    const observability = GeckoClient.observability;

    function validateUrls(list) {
        return (list || []).map(url => validURLString(url)).filter(url => !!url);
    }

    const CoinGecko = window.CoinGecko = {
        baseUrl: cg.gatewayBaseUrl || '/api/market/',
        cacheMap: new Map(),
        metaMap: new Map(),
        // cache expiration
        cacheClearTimeout: 5 * 60 * 1000,
        cacheKey: function (path, config) {
            return JSON.stringify([path, config]);
        },
        cacheHas: function (path, config) {
            return this.cacheMap.has(this.cacheKey(path, config));
        },
        cacheGet: function (path, config) {
            return this.cacheMap.get(this.cacheKey(path, config));
        },
        cacheSet: function (path, config, data) {
            return this.cacheMap.set(this.cacheKey(path, config), data);
        },
        metaSet: function (path, config, meta) {
            return this.metaMap.set(this.cacheKey(path, config), meta);
        },
        metaGet: function (path, config) {
            return this.metaMap.get(this.cacheKey(path, config));
        },
        cacheRegisterClearTimer: function () {
            this.cacheClearTimer = setInterval(() => {
                this.cacheMap.clear();
                this.metaMap.clear();
            }, this.cacheClearTimeout)
        },
        get: function (path, config, consistency, cache) {
            cache = cache || cg.cache;

            // immediately serve cached data
            if (cache && this.cacheHas(path, config)) return Promise.resolve(_.cloneDeep(this.cacheGet(path, config)));

            const client = axios.create({
                baseURL: this.baseUrl,
                timeout: cg.timeout > 0 ? cg.timeout : 0
            });
            if (observability && observability.instrumentAxiosInstance) {
                observability.instrumentAxiosInstance(client);
            }

            return client.get(path, config)
                .then((res) => {
                    const payload = res.data || {};
                    let data = payload && payload.ok === true && Object.prototype.hasOwnProperty.call(payload, 'data')
                        ? payload.data
                        : payload;

                    if (payload && payload.meta) {
                        this.metaSet(path, config, payload.meta);
                    }

                    // forces a type or transforms
                    if (consistency) {
                        if (_.isArray(consistency)) data = _.isArray(data) ? data : [];
                        else if (_.isFunction(consistency)) data = consistency(data);
                        else if (_.isObject(consistency)) data = _.isObject(data) ? data : {};
                    }

                    // sets cache if enabled
                    if (cache && data) this.cacheSet(path, config, data);

                    return _.cloneDeep(data);
                });
        },
        marketChartDataConsistency: data => {
            // ensures chart data integrity
            if (!data
                || !_.isArray(data.market_caps)
                || !_.isArray(data.prices)
                || !_.isArray(data.total_volumes)
                || data.market_caps.length !== data.prices.length
                || data.market_caps.length !== data.total_volumes.length) {
                return Promise.reject(new Error());
            }
            return data;
        },
        global: function (cache) {
            const consistency = data => data.data || {};

            return this.get('global', undefined, consistency, cache);
        },
        coinsMarkets: function (params, cache) {
            return this.get('coins/markets', {params: params}, [], cache);
        },
        coin: function (id, params, cache) {
            const consistency = currency => {
                currency = currency || {};

                currency.symbol = _.toLower(currency.symbol);
                currency.categories = _.uniq(currency.categories);
                currency.category = currency.categories[0];

                currency.platforms = currency.platforms || {};
                currency.platformList = _.map(currency.platforms, (address, name) => [_.startCase(name), address]).filter(c => c[0] && c[1]);

                const links = currency.links = currency.links || {};
                currency.websiteUrl = validURLString(_.first(links.homepage))
                currency.explorerUrls = validateUrls(links.blockchain_site);
                currency.announcementUrls = validateUrls(links.announcement_url);
                currency.forumUrls = validateUrls(links.official_forum_url);
                currency.chatUrls = validateUrls(links.chat_url);
                currency.redditUrl = validURLString(links.subreddit_url);
                currency.twitterUrl = validURLString(links.twitter_screen_name, 'https://twitter.com/');
                currency.facebookUrl = validURLString(links.facebook_username, 'https://www.facebook.com/');
                currency.bitcointalkId = links.bitcointalk_thread_identifier || null;
                currency.bitcointalkUrl = bitcointalkThreadUrl(currency.bitcointalkId);
                currency.customLinkUrl = getCustomLink('currencies', id);

                links.repos_url = links.repos_url || {};
                currency.githubUrls = validateUrls(links.repos_url.github);
                currency.bitbucketUrls = validateUrls(links.repos_url.bitbucket);

                currency.url = currency.customLinkUrl || currency.websiteUrl;

                return currency;
            };

            return this.get('coins/' + id, {params: params}, consistency, cache);
        },
        coinMarketChart: function (id, params, cache) {
            return this.get('coins/' + id + '/market_chart', {params: params}, this.marketChartDataConsistency, cache);
        },
        coinMarketChartRange: function (id, params, cache) {
            return this.get('coins/' + id + '/market_chart/range', {params: params}, this.marketChartDataConsistency, cache);
        },
        coinTickers: function (id, params, cache) {
            const consistency = data => _.get(data, 'tickers', []);
            return this.get('coins/' + id + '/tickers', {params: params}, consistency, cache)
        },
        exchanges: function (params, cache) {
            return this.get('exchanges', {params: params}, [], cache);
        },
        exchange: function (id, params, cache) {
            const consistency = exchange => {
                exchange = exchange || {};

                exchange.websiteUrl = validURLString(exchange.url);
                exchange.twitterUrl = validURLString(exchange.twitter_handle, 'https://twitter.com/');
                exchange.facebookUrl = validURLString(exchange.facebook_url, 'https://www.facebook.com/');
                exchange.redditUrl = validURLString(exchange.reddit_url, 'https://www.reddit.com/');
                exchange.telegramUrl = validURLString(exchange.telegram_url, 'https://t.me/');
                exchange.otherUrl1 = validURLString(exchange.other_url_1);
                exchange.otherUrl2 = validURLString(exchange.other_url_2);
                exchange.customLinkUrl = getCustomLink('exchanges', id);
                exchange.url = exchange.customLinkUrl || exchange.websiteUrl;

                return exchange;
            };

            return this.get('exchanges/' + id, {params: params}, consistency, cache);
        },
        exchangeTickers: function (id, params, cache) {
            const consistency = data => _.get(data, 'tickers', []);
            return this.get('exchanges/' + id + '/tickers', {params: params}, consistency, cache);
        },
        exchangeVolumeChart: function (id, params, cache) {
            return this.get('exchanges/' + id + '/volume_chart', {params: params}, [], cache);
        },
        search: function (cache) {
            const consistency = search => {
                return {
                    categories: search.categories || [],
                    coins: search.coins || [],
                    exchanges: search.exchanges || [],
                    icos: search.icos || []
                }
            };
            return this.get('search/', undefined, consistency, cache);
        },
        searchTrending: function (cache) {
            const consistency = trending => {
                trending = trending || {};
                trending.exchanges = _.map(trending.exchanges);
                trending.coins = _.map(trending.coins, 'item');
                return trending;
            };

            return this.get('search/trending', undefined, consistency, cache);
        },
        financePlatforms: function (params, cache) {
            const consistency = platforms => {
                return (platforms || []).map(platform => {
                    platform.customLinkUrl = getCustomLink('financePlatforms', platform.name);
                    platform.websiteUrl = validURLString(platform.website_url);
                    platform.url = platform.customLinkUrl || platform.websiteUrl;
                    platform.color = platform.centralized ? 'orange' : 'green';
                    platform.catLabel = platform.centralized ? __( 'CeFi' ) : __( 'DeFi' );
                    return platform;
                })
            };

            return this.get('finance_platforms', {params: params}, consistency, cache);
        },
        financeProducts: function (params, cache) {
            return this.get('finance_products', {params: params}, [], cache);
        },
        derivatives: function (params, cache) {
            return this.get('derivatives', {params: params}, [], cache);
        }
    };

    if (cg.cache) {
        CoinGecko.cacheRegisterClearTimer();
    }

})(window, _, axios, GeckoClient);

(function (window, _, axios, GeckoClient) {
    'use strict';

    const storageKey = 'TONBANKCARD:watchlist:v1';
    const storageTestKey = storageKey + ':test';
    const legacyStorageKeys = ['TONBANKCARD:watchlist', 'GeckoClient:watchlist'];
    const maxEntries = 200;
    const changedEventName = 'tonbankcard:watchlist-changed';

    let state = emptyState();
    let initialized = false;
    let initializing = null;
    let localStorageAvailable = null;
    let listeners = [];
    let serverSyncEnabled = false;
    let serverSyncAttempted = false;

    const service = GeckoClient.watchlist = {
        storageKey: storageKey,
        storageMode: 'local',
        init: init,
        ids: ids,
        snapshot: snapshot,
        has: has,
        add: add,
        remove: remove,
        toggle: toggle,
        onChange: onChange,
        normalizeEntry: normalizeEntry
    };

    function nowIso() {
        return new Date().toISOString();
    }

    function emptyState() {
        return {
            version: 1,
            updated_at: null,
            entries: [],
            removed: {}
        };
    }

    function clone(value) {
        return _.cloneDeep(value);
    }

    function parseDate(value) {
        const date = new Date(value || 0);
        return GeckoClient.utils.isValidDate(date) ? date.getTime() : 0;
    }

    function normalizeCoinId(value) {
        value = _.toLower(_.trim(value || ''));
        return /^[a-z0-9._-]{1,96}$/.test(value) ? value : null;
    }

    function normalizeSymbol(value) {
        value = _.toLower(_.trim(value || ''));
        return /^[a-z0-9._-]{1,32}$/.test(value) ? value : null;
    }

    function normalizeImage(value) {
        if (_.isString(value)) return value;
        if (_.isObject(value)) return value.large || value.small || value.thumb || null;
        return null;
    }

    function normalizeEntry(item) {
        if (_.isString(item)) {
            const id = normalizeCoinId(item);
            return id ? {
                coin_id: id,
                id: id,
                symbol: null,
                name: _.startCase(id),
                image: null,
                source: 'coingecko',
                added_at: nowIso(),
                updated_at: nowIso()
            } : null;
        }

        if (!_.isObject(item)) return null;

        const id = normalizeCoinId(item.coin_id || item.id);
        if (!id) return null;

        const symbol = normalizeSymbol(item.symbol);
        const addedAt = item.added_at || item.addedAt || nowIso();
        const updatedAt = item.updated_at || item.updatedAt || addedAt;

        return {
            coin_id: id,
            id: id,
            symbol: symbol,
            name: _.trim(item.name || item.title || '') || _.startCase(id),
            image: normalizeImage(item.image || item.large || item.small || item.thumb),
            source: _.trim(item.source || 'coingecko') || 'coingecko',
            added_at: addedAt,
            updated_at: updatedAt
        };
    }

    function normalizePayload(payload) {
        if (_.isString(payload)) {
            try {
                payload = JSON.parse(payload);
            } catch (err) {
                return emptyState();
            }
        }

        let entries = [];
        let removed = {};

        if (_.isArray(payload)) {
            entries = payload;
        } else if (_.isObject(payload)) {
            if (_.isArray(payload.entries)) {
                entries = payload.entries;
            } else {
                entries = _.keys(payload).map(key => {
                    const value = payload[key];
                    return _.isObject(value) ? Object.assign({}, value, {coin_id: key}) : key;
                });
            }

            if (_.isObject(payload.removed)) {
                removed = _.mapValues(payload.removed, value => _.isString(value) ? value : nowIso());
            }
        }

        const seen = {};
        const normalizedEntries = [];
        entries.forEach(item => {
            const entry = normalizeEntry(item);
            if (!entry || seen[entry.coin_id]) return;
            seen[entry.coin_id] = true;
            normalizedEntries.push(entry);
        });

        const normalizedRemoved = {};
        _.forOwn(removed, (removedAt, coinId) => {
            const id = normalizeCoinId(coinId);
            if (id) normalizedRemoved[id] = removedAt || nowIso();
        });

        return {
            version: 1,
            updated_at: _.get(payload, 'updated_at') || null,
            entries: normalizedEntries.slice(0, maxEntries),
            removed: normalizedRemoved
        };
    }

    function mergeState(base, incoming) {
        base = normalizePayload(base);
        incoming = normalizePayload(incoming);

        const entriesById = {};
        const removed = Object.assign({}, base.removed || {}, incoming.removed || {});

        function mergeEntry(entry) {
            const removedAt = parseDate(removed[entry.coin_id]);
            const entryUpdatedAt = parseDate(entry.updated_at || entry.added_at);
            if (removedAt > entryUpdatedAt) return;

            const existing = entriesById[entry.coin_id];
            if (!existing || parseDate(existing.updated_at || existing.added_at) <= entryUpdatedAt) {
                entriesById[entry.coin_id] = entry;
            }
        }

        base.entries.forEach(mergeEntry);
        incoming.entries.forEach(mergeEntry);

        const entries = _.values(entriesById)
            .filter(entry => parseDate(removed[entry.coin_id]) <= parseDate(entry.updated_at || entry.added_at))
            .sort((a, b) => parseDate(a.added_at) - parseDate(b.added_at))
            .slice(0, maxEntries);

        return {
            version: 1,
            updated_at: base.updated_at || incoming.updated_at || nowIso(),
            entries: entries,
            removed: removed
        };
    }

    function canUseLocalStorage() {
        if (localStorageAvailable !== null) return localStorageAvailable;

        try {
            window.localStorage.setItem(storageTestKey, '1');
            window.localStorage.removeItem(storageTestKey);
            localStorageAvailable = true;
        } catch (err) {
            localStorageAvailable = false;
        }

        return localStorageAvailable;
    }

    function readLocalStorage(key) {
        if (!canUseLocalStorage()) return null;

        try {
            return window.localStorage.getItem(key);
        } catch (err) {
            localStorageAvailable = false;
            return null;
        }
    }

    function writeLocalStorage(key, value) {
        if (!canUseLocalStorage()) return false;

        try {
            window.localStorage.setItem(key, value);
            return true;
        } catch (err) {
            localStorageAvailable = false;
            return false;
        }
    }

    function readLocalPayload() {
        const raw = readLocalStorage(storageKey);
        if (raw) return normalizePayload(raw);

        for (let i = 0; i < legacyStorageKeys.length; i++) {
            const legacyRaw = readLocalStorage(legacyStorageKeys[i]);
            if (legacyRaw) return normalizePayload(legacyRaw);
        }

        return emptyState();
    }

    function writeLocalPayload(payload) {
        return writeLocalStorage(storageKey, JSON.stringify(payload));
    }

    function getCloudStorage() {
        const cloudStorage = _.get(GeckoClient, 'telegram.webApp.CloudStorage') || _.get(window, 'Telegram.WebApp.CloudStorage');
        if (!cloudStorage || !_.isFunction(cloudStorage.getItem) || !_.isFunction(cloudStorage.setItem)) {
            return null;
        }

        return cloudStorage;
    }

    function cloudGet(cloudStorage) {
        return new Promise(resolve => {
            try {
                cloudStorage.getItem(storageKey, function (error, value) {
                    resolve(error ? null : value);
                });
            } catch (err) {
                resolve(null);
            }
        });
    }

    function cloudSet(cloudStorage, payload) {
        return new Promise(resolve => {
            try {
                cloudStorage.setItem(storageKey, JSON.stringify(payload), function (error) {
                    resolve(!error);
                });
            } catch (err) {
                resolve(false);
            }
        });
    }

    function loadStoredState() {
        const cloudStorage = getCloudStorage();
        if (cloudStorage) {
            service.storageMode = 'telegram_cloud';
            return cloudGet(cloudStorage).then(raw => {
                if (raw) return normalizePayload(raw);
                return readLocalPayload();
            });
        }

        if (!canUseLocalStorage()) {
            service.storageMode = 'memory';
            return Promise.resolve(state);
        }

        service.storageMode = 'local';
        return Promise.resolve(readLocalPayload());
    }

    function persistDeviceSnapshot(payload) {
        let stored;
        if (service.storageMode === 'telegram_cloud') {
            const cloudStorage = getCloudStorage();
            stored = cloudStorage ? cloudSet(cloudStorage, payload) : Promise.resolve(false);
        } else if (service.storageMode === 'memory') {
            stored = Promise.resolve(true);
        } else {
            stored = Promise.resolve(writeLocalPayload(payload));
        }

        return stored.then(ok => {
            if (!ok && service.storageMode !== 'memory') {
                service.storageMode = writeLocalPayload(payload) ? 'local' : 'memory';
            }

            return payload;
        });
    }

    function persistState() {
        state.version = 1;
        state.updated_at = nowIso();
        const payload = snapshot();

        return persistDeviceSnapshot(payload)
            .then(() => pushServerSnapshot())
            .then(() => payload);
    }

    function emitAnalytics(eventName, entry, options) {
        const properties = {
            coin_id: entry.coin_id,
            symbol: entry.symbol,
            source_route: _.get(options, 'sourceRoute') || _.get(options, 'source_route') || null,
            storage_mode: service.storageMode
        };

        if (GeckoClient.analytics) {
            GeckoClient.analytics.emit(eventName, properties);
        }
        if (eventName === 'watchlist_added' && GeckoClient.achievements) {
            GeckoClient.achievements.track('watchlist_added', properties);
        }
    }

    function notify() {
        const detail = snapshot();
        listeners.slice().forEach(callback => callback(detail));

        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent(changedEventName, {detail: detail}));
        }
    }

    function endpoint() {
        return _.get(GeckoClient, 'watchlistConfig.apiBaseUrl', '/api/watchlist');
    }

    function telegramInitData() {
        return _.get(GeckoClient, 'telegram.webApp.initData') || _.get(window, 'Telegram.WebApp.initData') || '';
    }

    function bootstrapServerSync() {
        if (serverSyncAttempted) return Promise.resolve();
        serverSyncAttempted = true;

        const initData = telegramInitData();
        if (!initData || !axios) return Promise.resolve();

        return axios.post('/api/telegram/session', {initData: initData}, {withCredentials: true})
            .then(response => {
                const payload = response.data || {};
                const trustState = _.get(payload, 'data.session.state');
                serverSyncEnabled = payload.ok === true && trustState === 'telegram_validated';
                if (!serverSyncEnabled) return;

                return pullServerSnapshot();
            })
            .catch(() => {
                serverSyncEnabled = false;
            });
    }

    function pullServerSnapshot() {
        if (!serverSyncEnabled || !axios) return Promise.resolve();

        return axios.get(endpoint(), {withCredentials: true})
            .then(response => {
                const payload = response.data || {};
                if (payload.ok !== true) return;

                state = mergeState(state, _.get(payload, 'data', {}));
                notify();
                return persistState();
            })
            .catch(() => {});
    }

    function pushServerSnapshot() {
        if (!serverSyncEnabled || !axios) return Promise.resolve();

        return axios.post(
            endpoint(),
            {
                entries: state.entries.map(entry => _.pick(entry, ['coin_id', 'symbol', 'source', 'added_at', 'updated_at'])),
                removed: state.removed,
                updated_at: state.updated_at
            },
            {withCredentials: true}
        ).then(response => {
            const payload = response.data || {};
            if (payload.ok === true && _.get(payload, 'data.entries')) {
                state = mergeState(state, payload.data);
                notify();
                return persistDeviceSnapshot(snapshot());
            }
        }).catch(() => {});
    }

    function init() {
        if (initialized) return Promise.resolve(service);
        if (initializing) return initializing;

        initializing = loadStoredState()
            .then(storedState => {
                state = mergeState(state, storedState);
                initialized = true;
                notify();
                return bootstrapServerSync();
            })
            .then(() => service);

        return initializing;
    }

    function ids() {
        return state.entries.map(entry => entry.coin_id);
    }

    function snapshot() {
        return clone({
            version: 1,
            updated_at: state.updated_at,
            entries: state.entries,
            removed: state.removed
        });
    }

    function has(item) {
        const id = normalizeCoinId(_.isString(item) ? item : _.get(item, 'coin_id') || _.get(item, 'id'));
        return !!id && ids().indexOf(id) >= 0;
    }

    function add(item, options) {
        const entry = normalizeEntry(item);
        if (!entry) return Promise.resolve(false);

        return init().then(() => {
            const existing = _.find(state.entries, ['coin_id', entry.coin_id]);
            const timestamp = nowIso();
            entry.added_at = existing ? existing.added_at : timestamp;
            entry.updated_at = timestamp;

            state.entries = state.entries.filter(current => current.coin_id !== entry.coin_id);
            state.entries.push(entry);
            state.entries = state.entries.slice(-maxEntries);
            delete state.removed[entry.coin_id];

            notify();
            emitAnalytics('watchlist_added', entry, options);
            return persistState().then(() => true);
        });
    }

    function remove(item, options) {
        const id = normalizeCoinId(_.isString(item) ? item : _.get(item, 'coin_id') || _.get(item, 'id'));
        if (!id) return Promise.resolve(false);

        return init().then(() => {
            const entry = _.find(state.entries, ['coin_id', id]) || normalizeEntry(id);
            const beforeCount = state.entries.length;
            state.entries = state.entries.filter(current => current.coin_id !== id);
            state.removed[id] = nowIso();

            notify();
            if (beforeCount !== state.entries.length) {
                emitAnalytics('watchlist_removed', entry, options);
            }

            return persistState().then(() => true);
        });
    }

    function toggle(item, options) {
        return init().then(() => has(item) ? remove(item, options) : add(item, options));
    }

    function onChange(callback) {
        if (!_.isFunction(callback)) return function () {};

        listeners.push(callback);
        return function unsubscribe() {
            listeners = listeners.filter(listener => listener !== callback);
        };
    }

})(window, _, axios, GeckoClient);

(function (window, _, axios, GeckoClient) {
    'use strict';

    const storageKey = 'TONBANKCARD:alerts:v1';
    const storageTestKey = storageKey + ':test';
    const changedEventName = 'tonbankcard:alerts-changed';
    const maxLocalRules = 50;

    let state = emptyState();
    let initialized = false;
    let initializing = null;
    let listeners = [];
    let localStorageAvailable = null;
    let serverSyncEnabled = false;
    let serverSyncAttempted = false;

    const service = GeckoClient.alerts = {
        storageKey: storageKey,
        storageMode: 'local',
        init: init,
        snapshot: snapshot,
        list: list,
        save: save,
        remove: remove,
        pause: pause,
        resume: resume,
        test: testDelivery,
        readDraft: readDraft,
        clearDraft: clearDraft,
        normalizeRule: normalizeRule,
        emptyRule: emptyRule,
        onChange: onChange
    };

    function nowIso() {
        return new Date().toISOString();
    }

    function emptyState() {
        return {
            version: 1,
            updated_at: null,
            rules: []
        };
    }

    function emptyRule(seed) {
        seed = seed || {};
        return {
            id: seed.id || null,
            coin_id: normalizeCoinId(seed.coin_id || 'toncoin') || 'toncoin',
            symbol: normalizeSymbol(seed.symbol || 'TON') || 'TON',
            display_name: _.trim(seed.display_name || seed.name || '') || null,
            trigger_type: seed.trigger_type || 'price_cross',
            operator: seed.operator || seed.comparison_operator || 'gte',
            threshold: _.isFinite(parseFloat(seed.threshold || seed.threshold_value)) ? parseFloat(seed.threshold || seed.threshold_value) : null,
            delivery_channel: 'telegram_bot',
            status: seed.status || 'active',
            quiet_start: seed.quiet_start || seed.quiet_hours_start || null,
            quiet_end: seed.quiet_end || seed.quiet_hours_end || null,
            timezone: seed.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
            frequency_cap_seconds: parseInt(seed.frequency_cap_seconds || _.get(GeckoClient, 'alertsDefaults.frequencyCapSeconds') || 3600, 10),
            max_deliveries_per_day: parseInt(seed.max_deliveries_per_day || 8, 10),
            context_path: seed.context_path || (seed.coin_id ? '/currency/' + seed.coin_id : '/currency/toncoin'),
            created_at: seed.created_at || nowIso(),
            updated_at: seed.updated_at || nowIso()
        };
    }

    function normalizeCoinId(value) {
        value = _.toLower(_.trim(value || ''));
        return /^[a-z0-9._-]{1,96}$/.test(value) ? value : null;
    }

    function normalizeSymbol(value) {
        value = _.toUpper(_.trim(value || ''));
        value = value.replace(/[^A-Z0-9._-]/g, '');
        return value ? value.slice(0, 32) : null;
    }

    function normalizeTime(value) {
        value = _.trim(value || '');
        if (!value) return null;
        const match = value.match(/^([01][0-9]|2[0-3]):([0-5][0-9])(?::([0-5][0-9]))?$/);
        return match ? match[1] + ':' + match[2] : null;
    }

    function normalizeTriggerType(value) {
        value = _.toLower(_.trim(value || 'price_cross'));
        return [
            'price_cross',
            'percent_move',
            'volume_spike',
            'market_cap_cross',
            'rank_change',
            'sentiment_change',
            'ton_ecosystem'
        ].indexOf(value) >= 0 ? value : 'price_cross';
    }

    function normalizeOperator(value) {
        value = _.toLower(_.trim(value || 'gte'));
        return ['gt', 'gte', 'lt', 'lte'].indexOf(value) >= 0 ? value : 'gte';
    }

    function normalizeRule(input) {
        if (!_.isObject(input)) return null;

        const seed = emptyRule(input);
        const coinId = normalizeCoinId(input.coin_id || seed.coin_id);
        if (!coinId) return null;

        const threshold = parseFloat(input.threshold !== undefined ? input.threshold : input.threshold_value);
        const rule = Object.assign(seed, {
            id: input.id || input.local_id || seed.id,
            local_id: input.local_id || (_.isString(input.id) && input.id.indexOf('local_') === 0 ? input.id : null),
            coin_id: coinId,
            symbol: normalizeSymbol(input.symbol) || seed.symbol,
            display_name: _.trim(input.display_name || input.name || seed.display_name || '') || null,
            trigger_type: normalizeTriggerType(input.trigger_type),
            operator: normalizeOperator(input.operator || input.comparison_operator),
            threshold: _.isFinite(threshold) ? threshold : seed.threshold,
            delivery_channel: 'telegram_bot',
            status: ['active', 'paused'].indexOf(input.status) >= 0 ? input.status : seed.status,
            quiet_start: normalizeTime(input.quiet_start || input.quiet_hours_start),
            quiet_end: normalizeTime(input.quiet_end || input.quiet_hours_end),
            timezone: _.trim(input.timezone || seed.timezone) || 'UTC',
            frequency_cap_seconds: clampInt(input.frequency_cap_seconds, 300, 86400, seed.frequency_cap_seconds),
            max_deliveries_per_day: clampInt(input.max_deliveries_per_day, 1, 100, seed.max_deliveries_per_day),
            context_path: safeContextPath(input.context_path, coinId),
            updated_at: input.updated_at || nowIso(),
            links: input.links || null,
            last_evaluated_at: input.last_evaluated_at || null,
            last_triggered_at: input.last_triggered_at || null,
            next_evaluation_at: input.next_evaluation_at || null
        });

        if (rule.threshold === null && rule.trigger_type !== 'ton_ecosystem') {
            rule.threshold = 0;
        }
        if (rule.trigger_type === 'ton_ecosystem' && rule.threshold === null) {
            rule.threshold = 0;
        }

        return rule;
    }

    function clampInt(value, min, max, fallback) {
        value = parseInt(value, 10);
        if (!_.isFinite(value)) return fallback;
        return Math.max(min, Math.min(max, value));
    }

    function safeContextPath(value, coinId) {
        value = _.trim(value || '');
        if (!value || value.charAt(0) !== '/' || value.indexOf('//') === 0) {
            return '/currency/' + coinId;
        }
        return value.slice(0, 190);
    }

    function normalizePayload(payload) {
        if (_.isString(payload)) {
            try {
                payload = JSON.parse(payload);
            } catch (err) {
                return emptyState();
            }
        }

        let rules = [];
        if (_.isArray(payload)) {
            rules = payload;
        } else if (_.isArray(_.get(payload, 'rules'))) {
            rules = payload.rules;
        }

        const seen = {};
        const normalized = [];
        rules.forEach(item => {
            const rule = normalizeRule(item);
            if (!rule) return;
            const key = String(rule.id || rule.local_id || rule.coin_id + ':' + rule.trigger_type);
            if (seen[key]) return;
            seen[key] = true;
            normalized.push(rule);
        });

        return {
            version: 1,
            updated_at: _.get(payload, 'updated_at') || nowIso(),
            rules: normalized.slice(0, maxLocalRules)
        };
    }

    function clone(value) {
        return _.cloneDeep(value);
    }

    function canUseLocalStorage() {
        if (localStorageAvailable !== null) return localStorageAvailable;

        try {
            window.localStorage.setItem(storageTestKey, '1');
            window.localStorage.removeItem(storageTestKey);
            localStorageAvailable = true;
        } catch (err) {
            localStorageAvailable = false;
        }

        return localStorageAvailable;
    }

    function readLocalPayload() {
        if (!canUseLocalStorage()) {
            service.storageMode = 'memory';
            return emptyState();
        }

        try {
            return normalizePayload(window.localStorage.getItem(storageKey));
        } catch (err) {
            localStorageAvailable = false;
            service.storageMode = 'memory';
            return emptyState();
        }
    }

    function writeLocalPayload(payload) {
        if (!canUseLocalStorage()) return false;
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(payload));
            return true;
        } catch (err) {
            localStorageAvailable = false;
            service.storageMode = 'memory';
            return false;
        }
    }

    function persistState() {
        state.version = 1;
        state.updated_at = nowIso();
        writeLocalPayload(snapshot());
        notify();
        return Promise.resolve(snapshot());
    }

    function endpoint(id, suffix) {
        let base = _.get(GeckoClient, 'alertsConfig.apiBaseUrl', '/api/alerts').replace(/\/+$/, '');
        if (id) base += '/' + encodeURIComponent(id);
        if (suffix) base += '/' + suffix;
        return base;
    }

    function telegramInitData() {
        return _.get(GeckoClient, 'telegram.webApp.initData') || _.get(window, 'Telegram.WebApp.initData') || '';
    }

    function bootstrapServerSync() {
        if (serverSyncAttempted) return Promise.resolve();
        serverSyncAttempted = true;

        const initData = telegramInitData();
        if (!initData || !axios) return Promise.resolve();

        return axios.post('/api/telegram/session', {initData: initData}, {withCredentials: true})
            .then(response => {
                const payload = response.data || {};
                const trustState = _.get(payload, 'data.session.state');
                serverSyncEnabled = payload.ok === true && trustState === 'telegram_validated';
                service.storageMode = serverSyncEnabled ? 'server' : service.storageMode;
                if (!serverSyncEnabled) return;
                return pullServerRules();
            })
            .catch(() => {
                serverSyncEnabled = false;
            });
    }

    function pullServerRules() {
        if (!serverSyncEnabled || !axios) return Promise.resolve();

        return axios.get(endpoint(), {withCredentials: true})
            .then(response => {
                const payload = response.data || {};
                if (payload.ok !== true) return;
                state = normalizePayload(_.get(payload, 'data', {}));
                writeLocalPayload(snapshot());
                notify();
            })
            .catch(() => {});
    }

    function serverRuleId(rule) {
        const id = _.get(rule, 'id');
        return /^[0-9]+$/.test(String(id || '')) ? String(id) : null;
    }

    function init() {
        if (initialized) return Promise.resolve(service);
        if (initializing) return initializing;

        initializing = Promise.resolve(readLocalPayload())
            .then(payload => {
                state = normalizePayload(payload);
                initialized = true;
                notify();
                return bootstrapServerSync();
            })
            .then(() => service);

        return initializing;
    }

    function snapshot() {
        return clone(state);
    }

    function list() {
        return snapshot().rules || [];
    }

    function upsertLocal(rule) {
        const key = String(rule.id || rule.local_id);
        state.rules = state.rules.filter(current => String(current.id || current.local_id) !== key);
        state.rules.unshift(rule);
        state.rules = state.rules.slice(0, maxLocalRules);
        return persistState();
    }

    function save(rule, options) {
        rule = normalizeRule(rule);
        if (!rule) return Promise.reject(new Error('invalid_alert_rule'));

        return init().then(() => {
            const existingId = serverRuleId(rule);
            if (serverSyncEnabled && axios) {
                const payload = rulePayload(rule);
                const request = existingId
                    ? axios.put(endpoint(existingId), payload, {withCredentials: true})
                    : axios.post(endpoint(), payload, {withCredentials: true});

                return request.then(response => {
                    const body = response.data || {};
                    const saved = normalizeRule(_.get(body, 'data.rule') || rule);
                    emitAnalytics(existingId ? 'alert_updated' : 'alert_created', saved, options);
                    return upsertLocal(saved).then(() => saved);
                });
            }

            const isNewLocal = !rule.id;
            if (isNewLocal) {
                rule.id = 'local_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
                rule.local_id = rule.id;
                rule.created_at = nowIso();
            }
            rule.updated_at = nowIso();
            emitAnalytics(isNewLocal ? 'alert_created' : 'alert_updated', rule, options);
            return upsertLocal(rule).then(() => rule);
        });
    }

    function rulePayload(rule) {
        return {
            coin_id: rule.coin_id,
            symbol: rule.symbol,
            display_name: rule.display_name,
            trigger_type: rule.trigger_type,
            operator: rule.operator,
            threshold: rule.threshold,
            delivery_channel: 'telegram_bot',
            status: rule.status,
            quiet_start: rule.quiet_start,
            quiet_end: rule.quiet_end,
            timezone: rule.timezone,
            frequency_cap_seconds: rule.frequency_cap_seconds,
            max_deliveries_per_day: rule.max_deliveries_per_day,
            context_path: rule.context_path
        };
    }

    function remove(rule, options) {
        return init().then(() => {
            const id = serverRuleId(rule);
            const localKey = String(_.get(rule, 'id') || _.get(rule, 'local_id'));
            const finish = () => {
                state.rules = state.rules.filter(current => String(current.id || current.local_id) !== localKey);
                emitAnalytics('alert_deleted', rule, options);
                return persistState().then(() => true);
            };

            if (serverSyncEnabled && axios && id) {
                return axios.delete(endpoint(id), {withCredentials: true}).then(finish);
            }

            return finish();
        });
    }

    function pause(rule, options) {
        rule = Object.assign({}, rule, {status: 'paused'});
        return save(rule, options).then(saved => {
            emitAnalytics('alert_paused', saved, options);
            return saved;
        });
    }

    function resume(rule, options) {
        return save(Object.assign({}, rule, {status: 'active'}), options);
    }

    function testDelivery(rule, options) {
        return init().then(() => {
            const id = serverRuleId(rule);
            if (serverSyncEnabled && axios && id) {
                return axios.post(endpoint(id, 'test'), {}, {withCredentials: true})
                    .then(response => {
                        emitAnalytics('alert_tested', rule, options);
                        return _.get(response, 'data.data.delivery') || null;
                    });
            }

            emitAnalytics('alert_tested', rule, options);
            return {
                text: 'Test alert: ' + (rule.symbol || rule.coin_id) + ' opens inside TONBANKCARD.',
                startapp: 'alert_' + (rule.id || rule.local_id || rule.coin_id),
                links: {
                    mini_app_path: '/app/alerts?coin=' + encodeURIComponent(rule.coin_id),
                    coin_path: rule.context_path || '/currency/' + rule.coin_id,
                    telegram_deep_link: '/app/alerts?coin=' + encodeURIComponent(rule.coin_id)
                }
            };
        });
    }

    function readDraft() {
        const key = _.get(GeckoClient, 'alertsConfig.draftStorageKey', 'TONBANKCARD:alertDraft');
        if (!canUseLocalStorage()) return null;
        try {
            const raw = window.localStorage.getItem(key);
            return raw ? JSON.parse(raw) : null;
        } catch (err) {
            return null;
        }
    }

    function clearDraft() {
        const key = _.get(GeckoClient, 'alertsConfig.draftStorageKey', 'TONBANKCARD:alertDraft');
        if (!canUseLocalStorage()) return;
        try {
            window.localStorage.removeItem(key);
        } catch (err) {}
    }

    function emitAnalytics(eventName, rule, options) {
        const properties = {
            alert_id: String(rule.id || rule.local_id || ''),
            coin_id: rule.coin_id,
            symbol: rule.symbol,
            trigger_type: rule.trigger_type,
            delivery_channel: 'telegram_bot',
            threshold_bucket: thresholdBucket(rule.threshold),
            quiet_hours_enabled: !!(rule.quiet_start && rule.quiet_end),
            status: rule.status,
            source_route: _.get(options, 'sourceRoute') || _.get(options, 'source_route') || null,
            storage_mode: service.storageMode
        };

        if (GeckoClient.analytics) {
            GeckoClient.analytics.emit(eventName, properties);
        }
        if (eventName === 'alert_created' && GeckoClient.achievements) {
            GeckoClient.achievements.track('alert_created', properties);
        }
    }

    function thresholdBucket(value) {
        value = Math.abs(parseFloat(value) || 0);
        if (value === 0) return 'zero';
        if (value < 1) return 'sub_1';
        if (value < 10) return '1_10';
        if (value < 100) return '10_100';
        return '100_plus';
    }

    function notify() {
        const detail = snapshot();
        listeners.slice().forEach(callback => callback(detail));

        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent(changedEventName, {detail: detail}));
        }
    }

    function onChange(callback) {
        if (!_.isFunction(callback)) return function () {};
        listeners.push(callback);
        return function unsubscribe() {
            listeners = listeners.filter(listener => listener !== callback);
        };
    }

})(window, _, axios, GeckoClient);

(function (window, _, axios, GeckoClient) {
    'use strict';

    const config = GeckoClient.premiumConfig || {};
    let entitlement = emptyEntitlement();
    let initialized = false;
    let initializing = null;
    let sessionAttempted = false;

    const service = GeckoClient.premium = {
        settings: normalizeSettings(config.settings || {}),
        plans: normalizePlans(config.plans || []),
        init: init,
        entitlement: snapshot,
        fetchPlans: fetchPlans,
        fetchEntitlement: fetchEntitlement,
        checkout: checkout,
        cancel: cancel,
        openInvoice: openInvoice
    };

    function apiBaseUrl() {
        return _.trimEnd(config.apiBaseUrl || '/api/premium', '/');
    }

    function endpoint(suffix) {
        return suffix ? apiBaseUrl() + '/' + suffix : apiBaseUrl();
    }

    function normalizeSettings(source) {
        return {
            enabled: source.enabled === true,
            checkout_enabled: source.checkout_enabled === true,
            provider: _.toString(source.provider || 'telegram_stars'),
            currency: _.toString(source.currency || 'XTR'),
            premium_plan_code: _.toString(source.premium_plan_code || 'premium_monthly'),
            subscription_period_seconds: parseInt(source.subscription_period_seconds, 10) || 2592000,
            telegram_bot_configured: source.telegram_bot_configured === true,
            bot_username: _.toString(source.bot_username || '')
        };
    }

    function normalizePlans(items) {
        return (items || []).map(plan => {
            const limits = plan.limits || {};
            return {
                code: _.toString(plan.code || ''),
                name: _.toString(plan.name || ''),
                description: _.toString(plan.description || ''),
                price_stars: parseInt(plan.price_stars, 10) || 0,
                currency: _.toString(plan.currency || 'XTR'),
                recurring: plan.recurring === true,
                subscription_period_seconds: plan.subscription_period_seconds || null,
                duration_seconds: plan.duration_seconds || null,
                limits: {
                    alerts_per_user: parseInt(limits.alerts_per_user || limits.alerts, 10) || 0,
                    watchlist_entries: parseInt(limits.watchlist_entries || limits.watchlist, 10) || 0,
                    advanced_ranges: _.isArray(limits.advanced_ranges) ? limits.advanced_ranges : [],
                    ai_digest_per_day: parseInt(limits.ai_digest_per_day, 10) || 0,
                    priority_refresh: limits.priority_refresh === true,
                    market_refresh_seconds: parseInt(limits.market_refresh_seconds, 10) || 0
                }
            };
        }).filter(plan => plan.code);
    }

    function emptyEntitlement() {
        return {
            plan_code: 'free',
            entitled: false,
            status: 'free',
            source: null,
            reason: 'initial',
            limits: {},
            entitlement: null,
            upgrade_plan_code: 'premium_monthly'
        };
    }

    function normalizeEntitlement(source) {
        return Object.assign(emptyEntitlement(), _.isPlainObject(source) ? source : {});
    }

    function telegramInitData() {
        return _.get(GeckoClient, 'telegram.webApp.initData') || _.get(window, 'Telegram.WebApp.initData') || '';
    }

    function bootstrapSession() {
        if (sessionAttempted) return Promise.resolve();
        sessionAttempted = true;

        const initData = telegramInitData();
        if (!initData || !axios) return Promise.resolve();

        return axios.post('/api/telegram/session', {initData: initData}, {withCredentials: true}).catch(() => {});
    }

    function init() {
        if (initialized) return Promise.resolve(service);
        if (initializing) return initializing;

        initializing = bootstrapSession()
            .then(fetchEntitlement)
            .catch(() => {})
            .then(() => {
                initialized = true;
                return service;
            });

        return initializing;
    }

    function snapshot() {
        return _.cloneDeep(entitlement);
    }

    function fetchPlans() {
        if (!axios) return Promise.resolve({settings: service.settings, plans: service.plans});

        return axios.get(endpoint('plans'), {withCredentials: true}).then(response => {
            const data = _.get(response, 'data.data', {});
            service.settings = normalizeSettings(data.settings || service.settings);
            service.plans = normalizePlans(data.plans || service.plans);
            return {settings: service.settings, plans: service.plans};
        });
    }

    function fetchEntitlement() {
        if (!axios) return Promise.resolve(snapshot());

        return axios.get(endpoint('entitlement'), {withCredentials: true}).then(response => {
            entitlement = normalizeEntitlement(_.get(response, 'data.data.entitlement', {}));
            return snapshot();
        });
    }

    function checkout(planCode) {
        if (!axios) return Promise.reject(new Error('Checkout API is unavailable.'));

        return bootstrapSession()
            .then(() => axios.post(endpoint('checkout'), {plan_code: planCode}, {withCredentials: true}))
            .then(response => _.get(response, 'data.data', {}));
    }

    function cancel() {
        if (!axios) return Promise.reject(new Error('Premium API is unavailable.'));

        return axios.post(endpoint('entitlement/cancel'), {}, {withCredentials: true}).then(response => {
            entitlement = normalizeEntitlement(_.get(response, 'data.data.entitlement', entitlement));
            return snapshot();
        });
    }

    function openInvoice(invoiceLink) {
        if (!invoiceLink) return;

        const webApp = _.get(GeckoClient, 'telegram.webApp') || _.get(window, 'Telegram.WebApp');
        if (_.isFunction(_.get(webApp, 'openInvoice'))) {
            webApp.openInvoice(invoiceLink, function () {
                fetchEntitlement().catch(() => {});
            });
            return;
        }
        if (_.isFunction(_.get(webApp, 'openTelegramLink'))) {
            webApp.openTelegramLink(invoiceLink);
            return;
        }

        window.location.href = invoiceLink;
    }

})(window, _, axios, GeckoClient);

(function (window, _, GeckoClient) {
    'use strict';

    const config = GeckoClient.tonConnect || {};
    const storageKey = config.storageKey || 'TONBANKCARD:ton-connect-wallet:v1';
    const changedEventName = 'tonbankcard:ton-connect-changed';
    const forbiddenSecretKeys = ['private_key', 'privateKey', 'seed', 'seedPhrase', 'seed_phrase', 'mnemonic'];
    const forbiddenSecretKeyPattern = /(private|seed|mnemonic|secret)/i;

    let state = {
        status: config.enabled ? 'disconnected' : 'feature_disabled',
        wallet: null,
        error: '',
        sdkLoaded: false
    };
    let initialized = false;
    let initializing = null;
    let tonConnectUI = null;
    let statusUnsubscribe = null;
    let listeners = [];

    const service = GeckoClient.tonConnect = {
        enabled: !!config.enabled,
        manifestUrl: config.manifestUrl || '/tonconnect-manifest.json',
        sdkUrl: config.sdkUrl || '',
        storageKey: storageKey,
        init: init,
        connect: connect,
        disconnect: disconnect,
        clear: clear,
        snapshot: snapshot,
        onChange: onChange,
        normalizeWallet: normalizeWallet
    };

    function clone(value) {
        return _.cloneDeep(value);
    }

    function nowIso() {
        return new Date().toISOString();
    }

    function normalizeAddress(value) {
        value = _.trim(value || '');
        return /^[A-Za-z0-9:_+=/-]{8,128}$/.test(value) ? value : null;
    }

    function normalizeText(value, maxLength) {
        value = _.trim(value || '');
        if (!value) return null;
        value = value.replace(/[\x00-\x1F\x7F]+/g, ' ');
        return value.slice(0, maxLength || 120);
    }

    function normalizeNetwork(value) {
        value = _.toLower(_.trim(value || ''));
        if (value === '-239' || value === 'mainnet' || value === 'ton-mainnet') return 'mainnet';
        if (value === '-3' || value === 'testnet' || value === 'ton-testnet') return 'testnet';
        return value ? normalizeText(value, 32) : 'unknown';
    }

    function normalizeFeature(feature) {
        if (_.isString(feature)) return normalizeText(feature, 48);
        if (_.isObject(feature)) return normalizeText(feature.name || feature.method || feature.type, 48);
        return null;
    }

    function containsForbiddenSecretShape(value) {
        if (!_.isObject(value)) return false;

        const stack = [value];
        while (stack.length) {
            const current = stack.pop();
            if (!_.isObject(current)) continue;

            const keys = Object.keys(current);
            for (let i = 0; i < keys.length; i++) {
                if (forbiddenSecretKeys.indexOf(keys[i]) >= 0 || forbiddenSecretKeyPattern.test(keys[i])) return true;
                if (_.isObject(current[keys[i]])) stack.push(current[keys[i]]);
            }
        }

        return false;
    }

    function normalizeWallet(rawWallet) {
        if (!rawWallet || containsForbiddenSecretShape(rawWallet)) return null;

        const account = _.get(rawWallet, 'account') || rawWallet;
        const address = normalizeAddress(_.get(account, 'address'));
        if (!address) return null;

        const device = _.get(rawWallet, 'device', {});
        const walletInfo = _.get(rawWallet, 'walletInfo', {});
        const rawFeatures = _.get(device, 'features') || _.get(rawWallet, 'features') || _.get(walletInfo, 'features') || [];
        const supportedFeatures = _.uniq((_.isArray(rawFeatures) ? rawFeatures : [])
            .map(normalizeFeature)
            .filter(Boolean))
            .slice(0, 12);

        return {
            address: address,
            network: normalizeNetwork(_.get(account, 'chain') || _.get(account, 'network')),
            chain: normalizeText(_.get(account, 'chain') || _.get(account, 'network'), 32),
            wallet_name: normalizeText(_.get(rawWallet, 'name') || _.get(walletInfo, 'name') || _.get(device, 'appName'), 80),
            app_name: normalizeText(_.get(device, 'appName') || _.get(walletInfo, 'name') || _.get(rawWallet, 'name'), 80),
            app_version: normalizeText(_.get(device, 'appVersion'), 32),
            platform: normalizeText(_.get(device, 'platform'), 48),
            provider: normalizeText(_.get(rawWallet, 'provider') || _.get(walletInfo, 'bridgeUrl') || 'tonconnect', 96),
            image_url: GeckoClient.utils.validURLString(_.get(walletInfo, 'imageUrl') || _.get(rawWallet, 'imageUrl') || '', window.location.href),
            supported_features: supportedFeatures,
            connected_at: normalizeText(_.get(rawWallet, 'connected_at'), 40) || nowIso()
        };
    }

    function canUseLocalStorage() {
        try {
            const testKey = storageKey + ':test';
            window.localStorage.setItem(testKey, '1');
            window.localStorage.removeItem(testKey);
            return true;
        } catch (err) {
            return false;
        }
    }

    function readStoredWallet() {
        if (!canUseLocalStorage()) return null;

        try {
            const raw = window.localStorage.getItem(storageKey);
            return raw ? normalizeWallet(JSON.parse(raw)) : null;
        } catch (err) {
            return null;
        }
    }

    function writeStoredWallet(wallet) {
        if (!canUseLocalStorage()) return false;

        try {
            window.localStorage.setItem(storageKey, JSON.stringify(wallet));
            return true;
        } catch (err) {
            return false;
        }
    }

    function removeStoredWallet() {
        try {
            window.localStorage.removeItem(storageKey);
        } catch (err) {}
    }

    function setState(patch) {
        state = Object.assign({}, state, patch || {});
        notify();
        return snapshot();
    }

    function setWallet(wallet, persist) {
        wallet = normalizeWallet(wallet);
        if (!wallet) {
            removeStoredWallet();
            return setState({
                status: service.enabled ? 'disconnected' : 'feature_disabled',
                wallet: null,
                error: ''
            });
        }

        if (persist !== false) {
            writeStoredWallet(wallet);
        }

        return setState({
            status: 'connected',
            wallet: wallet,
            error: ''
        });
    }

    function snapshot() {
        return clone({
            enabled: service.enabled,
            status: state.status,
            wallet: state.wallet,
            error: state.error,
            sdkLoaded: state.sdkLoaded,
            manifestUrl: service.manifestUrl
        });
    }

    function notify() {
        const detail = snapshot();
        listeners.slice().forEach(callback => callback(detail));

        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent(changedEventName, {detail: detail}));
        }
    }

    function sdkConstructor() {
        return _.get(window, 'TON_CONNECT_UI.TonConnectUI') || _.get(window, 'TonConnectUI') || null;
    }

    function loadSdk() {
        const Constructor = sdkConstructor();
        if (Constructor) {
            state.sdkLoaded = true;
            return Promise.resolve(Constructor);
        }

        if (!service.sdkUrl || !GeckoClient.loadScript) {
            return Promise.reject(new Error('TON Connect UI SDK is not configured'));
        }

        return GeckoClient.loadScript(service.sdkUrl).then(() => {
            const LoadedConstructor = sdkConstructor();
            if (!LoadedConstructor) {
                throw new Error('TON Connect UI SDK did not expose TON_CONNECT_UI');
            }
            state.sdkLoaded = true;
            return LoadedConstructor;
        });
    }

    function ensureUi() {
        if (tonConnectUI) return Promise.resolve(tonConnectUI);

        return loadSdk().then(Constructor => {
            tonConnectUI = new Constructor({
                manifestUrl: service.manifestUrl
            });

            if (_.isFunction(tonConnectUI.onStatusChange)) {
                statusUnsubscribe = tonConnectUI.onStatusChange(wallet => {
                    if (wallet) setWallet(wallet, true);
                    else clear();
                });
            }

            const existingWallet = _.get(tonConnectUI, 'wallet') || (_.get(tonConnectUI, 'connected') ? tonConnectUI : null);
            if (existingWallet) {
                setWallet(existingWallet, true);
            }

            return tonConnectUI;
        });
    }

    function init() {
        if (initialized) return Promise.resolve(service);
        if (initializing) return initializing;

        initializing = Promise.resolve()
            .then(() => {
                initialized = true;
                const storedWallet = readStoredWallet();
                if (storedWallet) {
                    setWallet(storedWallet, false);
                } else {
                    notify();
                }
                return service;
            });

        return initializing;
    }

    function connect() {
        if (!service.enabled) {
            return Promise.resolve(setState({
                status: 'feature_disabled',
                error: 'TON Connect is disabled in this environment.'
            }));
        }

        setState({status: 'connecting', error: ''});

        return ensureUi()
            .then(ui => {
                if (_.isFunction(ui.connectWallet)) {
                    return ui.connectWallet();
                }
                if (_.isFunction(ui.openModal)) {
                    ui.openModal();
                    return null;
                }
                throw new Error('TON Connect UI connect method is unavailable');
            })
            .then(wallet => {
                if (wallet) {
                    return setWallet(wallet, true);
                }
                return setState({status: state.wallet ? 'connected' : 'disconnected'});
            })
            .catch(err => setState({
                status: state.wallet ? 'connected' : 'disconnected',
                error: err && err.message ? err.message : 'TON Connect wallet connection failed.'
            }));
    }

    function disconnect() {
        const disconnectUi = tonConnectUI && _.isFunction(tonConnectUI.disconnect)
            ? Promise.resolve().then(() => tonConnectUI.disconnect()).catch(() => null)
            : Promise.resolve(null);

        return disconnectUi.then(() => {
            return clear();
        });
    }

    function clear() {
        removeStoredWallet();
        return setState({
            status: service.enabled ? 'disconnected' : 'feature_disabled',
            wallet: null,
            error: ''
        });
    }

    function onChange(callback) {
        if (!_.isFunction(callback)) return function () {};

        listeners.push(callback);
        callback(snapshot());
        return function unsubscribe() {
            listeners = listeners.filter(listener => listener !== callback);
        };
    }

    service.destroy = function () {
        if (_.isFunction(statusUnsubscribe)) {
            statusUnsubscribe();
        }
        statusUnsubscribe = null;
    };

})(window, _, GeckoClient);

(function (window, _, axios, GeckoClient) {
    'use strict';

    const config = GeckoClient.aiConfig || {};
    const observability = GeckoClient.observability;
    const advicePattern = /\b(buy|sell|hold|short(?!-term)|long(?!-term)|leverage|take profit|stop loss|position size|all in)\b/i;

    const retryConfig = _.assign(
        {
            maxAttempts: 3,
            baseDelayMs: 600,
            maxDelayMs: 4000,
            retryableReasons: [
                'provider_unavailable',
                'provider_timeout',
                'provider_rate_limited',
                'provider_invalid_json',
                'schema_validation_failed',
                'client_unavailable'
            ]
        },
        _.isObject(config.retry) ? config.retry : {}
    );

    const client = axios.create({
        baseURL: config.apiBaseUrl || '/api/ai',
        timeout: config.timeoutMs || 12000
    });

    if (observability && observability.instrumentAxiosInstance) {
        observability.instrumentAxiosInstance(client);
    }

    function envelopeData(response) {
        const payload = response && response.data ? response.data : {};
        const data = payload && payload.ok === true && Object.prototype.hasOwnProperty.call(payload, 'data')
            ? payload.data
            : payload;

        if (data && _.isObject(data)) {
            data.request_id = _.get(payload, 'meta.request_id', null);
        }

        return data || {};
    }

    function cleanNumber(value) {
        const number = parseFloat(value);
        return _.isFinite(number) ? number : null;
    }

    function validDate(value) {
        const date = new Date(value || 0);
        return GeckoClient.utils.isValidDate(date) ? date : null;
    }

    function marketDataUpdatedAt(meta) {
        return _.get(meta, 'freshness.last_updated_at')
            || _.get(meta, 'freshness.fetched_at')
            || _.get(meta, 'last_updated_at')
            || _.get(meta, 'updated_at')
            || null;
    }

    function marketDataAgeSeconds(meta) {
        const directAge = _.get(meta, 'freshness.market_data_age_seconds');
        if (_.isFinite(parseFloat(directAge))) {
            return Math.max(0, parseInt(directAge, 10));
        }

        const timestamp = marketDataUpdatedAt(meta);
        const date = validDate(timestamp);
        if (!date) return 0;

        return Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
    }

    function containsAdvice(value) {
        if (_.isString(value)) return advicePattern.test(value);
        if (_.isArray(value)) return value.some(item => containsAdvice(item));
        if (_.isObject(value)) return _.values(value).some(item => containsAdvice(item));
        return false;
    }

    function unavailable(reason) {
        return {
            status: 'insight unavailable',
            insight: null,
            reason: reason || 'client_unavailable'
        };
    }

    function isRetryableReason(reason) {
        return _.includes(retryConfig.retryableReasons, reason);
    }

    function retryDelayMs(attempt) {
        const base = Math.max(0, parseInt(retryConfig.baseDelayMs, 10) || 0);
        const max = Math.max(base, parseInt(retryConfig.maxDelayMs, 10) || 0);
        const exponential = base * Math.pow(2, Math.max(0, attempt - 1));
        const jitter = Math.floor(Math.random() * Math.max(1, base / 2));
        return Math.min(max, exponential + jitter);
    }

    function delay(ms) {
        return new Promise(resolve => window.setTimeout(resolve, ms));
    }

    function insightAttempt(body) {
        return client.post('insight', body)
            .then(response => {
                const data = envelopeData(response);
                if (data.status === 'available' && data.insight && containsAdvice(data.insight)) {
                    return unavailable('unsafe_output_blocked');
                }
                return data;
            })
            .catch(error => {
                const code = _.get(error, 'code') || _.get(error, 'response.status');
                const reason = code === 'ECONNABORTED' ? 'provider_timeout'
                    : code === 429 ? 'provider_rate_limited'
                    : 'provider_unavailable';
                return unavailable(reason);
            });
    }

    function insight(payload, options) {
        if (!payload || !payload.insight_type || !payload.subject) {
            return Promise.resolve(unavailable('missing_context'));
        }

        const body = Object.assign({}, payload, {
            market_data_age_seconds: Math.max(0, parseInt(payload.market_data_age_seconds || 0, 10))
        });

        const opts = _.assign({attempt: 1}, options || {});
        const maxAttempts = Math.max(1, parseInt(retryConfig.maxAttempts, 10) || 1);

        return insightAttempt(body).then(result => {
            const reason = result && result.status !== 'available' ? result.reason : null;
            if (!reason || opts.attempt >= maxAttempts || !isRetryableReason(reason)) {
                if (result && _.isObject(result)) {
                    result.attempts = opts.attempt;
                }
                return result;
            }

            if (_.isFunction(opts.onRetry)) {
                opts.onRetry({attempt: opts.attempt, reason: reason});
            }

            return delay(retryDelayMs(opts.attempt))
                .then(() => insight(payload, _.assign({}, opts, {attempt: opts.attempt + 1})));
        });
    }

    function feedback(insightPayload, feedbackType, context, sourceRoute) {
        insightPayload = insightPayload || {};
        context = context || {};

        if (!insightPayload.id) {
            return Promise.reject(new Error('missing_insight_id'));
        }

        const provider = insightPayload.provider || {};
        const body = {
            feedback_type: feedbackType,
            insight_type: insightPayload.type || context.insight_type,
            insight_id: insightPayload.id,
            subject: context.subject,
            provider: provider.name || _.get(context, 'provider.name'),
            model: provider.model_id || _.get(context, 'provider.model_id'),
            prompt_version: insightPayload.prompt_version || provider.prompt_version || _.get(context, 'prompt_version'),
            route_path: window.location.pathname || '/',
            source_route: sourceRoute,
            surface: GeckoClient.analytics ? GeckoClient.analytics.surface() : 'public_web',
            market_data_age_seconds: cleanNumber(_.get(insightPayload, 'freshness.market_data_age_seconds')) || context.market_data_age_seconds || 0,
            metadata: {
                card_title: context.card_title || null,
                source_route: sourceRoute || null,
                request_id: insightPayload.request_id || null
            }
        };

        return client.post('feedback', body).then(response => envelopeData(response));
    }

    function marketCurrencySnapshot(currency) {
        return {
            id: _.get(currency, 'id', null),
            symbol: _.get(currency, 'symbol', null),
            name: _.get(currency, 'name', null),
            rank: _.get(currency, 'market_cap_rank', null),
            price: cleanNumber(_.get(currency, 'current_price', _.get(currency, 'currentPrice'))),
            change_24h: cleanNumber(_.get(currency, 'price_change_percentage_24h_in_currency', _.get(currency, 'change24hPercent'))),
            market_cap: cleanNumber(_.get(currency, 'market_cap', _.get(currency, 'marketCap'))),
            volume_24h: cleanNumber(_.get(currency, 'total_volume', _.get(currency, 'totalVolume')))
        };
    }

    GeckoClient.ai = {
        containsAdvice: containsAdvice,
        feedback: feedback,
        insight: insight,
        isRetryableReason: isRetryableReason,
        retryConfig: retryConfig,
        marketCurrencySnapshot: marketCurrencySnapshot,
        marketDataAgeSeconds: marketDataAgeSeconds,
        marketDataUpdatedAt: marketDataUpdatedAt
    };

})(window, _, axios, GeckoClient);

(function (window, _, Vue, GeckoClient) {
    'use strict';

    const feedbackOptions = [
        {value: 'helpful', label: 'Helpful', icon: 'mdi-thumb-up-outline'},
        {value: 'stale', label: 'Stale', icon: 'mdi-clock-alert-outline'},
        {value: 'wrong', label: 'Wrong', icon: 'mdi-alert-circle-outline'},
        {value: 'unsafe', label: 'Unsafe', icon: 'mdi-shield-alert-outline'}
    ];

    Vue.component('gc-ai-insight-card', {
        template: '#component-ai-insight-card',
        props: {
            title: {
                type: String,
                required: true
            },
            icon: {
                type: String,
                default: 'mdi-brain'
            },
            context: {
                type: Object,
                default: null
            },
            sourceRoute: {
                type: String,
                default: null
            }
        },
        data: function () {
            return {
                loading: false,
                result: null,
                feedbackState: '',
                feedbackSubmitting: false,
                attempts: 0,
                fetchToken: 0
            };
        },
        computed: {
            contextSignature: function () {
                return JSON.stringify(this.context || {});
            },
            canRequest: function () {
                return !!(this.context && this.context.insight_type && this.context.subject);
            },
            insight: function () {
                return this.result && this.result.status === 'available' ? this.result.insight : null;
            },
            unavailableReason: function () {
                const labels = {
                    ai_disabled: 'AI disabled',
                    provider_not_configured: 'Provider not configured',
                    provider_unavailable: 'Provider unavailable',
                    provider_timeout: 'Provider timeout',
                    provider_rate_limited: 'Provider rate limited',
                    provider_invalid_json: 'Provider returned invalid response',
                    feature_disabled: 'Feature disabled',
                    schema_validation_failed: 'Safety validation blocked output',
                    unsafe_output_blocked: 'Safety validation blocked output',
                    missing_context: 'Insight context unavailable',
                    client_unavailable: 'Network unavailable'
                };
                const reason = this.result && this.result.reason ? this.result.reason : '';
                return labels[reason] || (reason ? _.startCase(reason) : 'Insight unavailable');
            },
            canRetry: function () {
                if (this.loading || !this.canRequest || !GeckoClient.ai) return false;
                if (!this.result || this.result.status === 'available') return false;
                return GeckoClient.ai.isRetryableReason(this.result.reason);
            },
            feedbackOptions: function () {
                return feedbackOptions;
            },
            insightShareCard: function () {
                if (!this.insight) return null;

                return {
                    title: this.insight.title || this.title,
                    subtitle: 'AI insight summary',
                    body: this.insight.summary || 'AI market insight summary from TONBANKCARD.',
                    route: GeckoClient.share ? GeckoClient.share.currentRoute() : (window.location.pathname || '/'),
                    campaign: 'ai-insight',
                    context: 'ai_insight',
                    freshness: this.freshnessLabel(this.insight),
                    metrics: [
                        {label: 'Sentiment', value: _.startCase(this.insight.sentiment || 'neutral')},
                        {label: 'Confidence', value: this.confidenceLabel(this.insight.confidence)},
                        {label: 'Source', value: this.sourceRoute || 'market_view'},
                        {label: 'Provider', value: this.insight.provider || 'configured AI'}
                    ]
                };
            }
        },
        watch: {
            contextSignature: {
                immediate: true,
                handler: function () {
                    this.fetchInsight();
                }
            }
        },
        methods: {
            fetchInsight: function () {
                this.feedbackState = '';

                if (!this.canRequest || !GeckoClient.ai) {
                    this.result = null;
                    this.loading = false;
                    return;
                }

                const context = Object.assign({}, this.context, {card_title: this.title});
                const token = ++this.fetchToken;
                this.loading = true;
                this.attempts = 1;

                GeckoClient.ai.insight(context, {
                    onRetry: payload => {
                        if (token !== this.fetchToken) return;
                        this.attempts = (payload && payload.attempt ? payload.attempt : this.attempts) + 1;
                    }
                })
                    .then(result => {
                        if (token !== this.fetchToken) return;
                        result = result || {};
                        if (result.attempts) this.attempts = result.attempts;
                        if (result.insight) {
                            result.insight.provider = result.provider || null;
                            result.insight.prompt_version = result.prompt_version || null;
                            result.insight.request_id = result.request_id || null;
                        }
                        this.result = result;
                    })
                    .catch(() => {
                        if (token !== this.fetchToken) return;
                        this.result = {
                            status: 'insight unavailable',
                            reason: 'client_unavailable'
                        };
                    })
                    .finally(() => {
                        if (token !== this.fetchToken) return;
                        this.loading = false;
                    });
            },
            retryFetch: function () {
                if (!this.canRetry) return;
                this.fetchInsight();
            },
            confidenceLabel: function (confidence) {
                const value = Math.max(0, Math.min(1, parseFloat(confidence) || 0));
                return Math.round(value * 100) + '% confidence';
            },
            confidenceColor: function (confidence) {
                const value = parseFloat(confidence) || 0;
                if (value >= 0.7) return 'success';
                if (value >= 0.4) return 'warning';
                return 'grey';
            },
            sentimentColor: function (sentiment) {
                if (sentiment === 'bullish') return 'success';
                if (sentiment === 'bearish') return 'error';
                if (sentiment === 'mixed') return 'warning';
                return 'grey';
            },
            freshnessLabel: function (insight) {
                const seconds = parseInt(_.get(insight, 'freshness.market_data_age_seconds', 0), 10);
                if (!_.isFinite(seconds) || seconds < 60) return 'Fresh source';
                if (seconds < 3600) return Math.floor(seconds / 60) + 'm source age';
                if (seconds < 86400) return Math.floor(seconds / 3600) + 'h source age';
                return Math.floor(seconds / 86400) + 'd source age';
            },
            submitFeedback: function (feedbackType) {
                if (!this.insight || !GeckoClient.ai || this.feedbackSubmitting) return;

                this.feedbackSubmitting = true;
                this.feedbackState = '';

                GeckoClient.ai.feedback(this.insight, feedbackType, this.context, this.sourceRoute)
                    .then(() => this.feedbackState = 'saved')
                    .catch(() => this.feedbackState = 'failed')
                    .finally(() => this.feedbackSubmitting = false);
            },
            shareInsight: function () {
                if (!this.insight || !GeckoClient.share) return;
                GeckoClient.share.share(this.insightShareCard);
            }
        }
    });

})(window, _, Vue, GeckoClient);

(function (window, _, Vue, GeckoClient) {
    'use strict';

    const preferences = GeckoClient.preferences;

    Vue.component('gc-cookies-dialog', {
        props: {
            expirationDays: {
                default: 30,
                validator: value => !isNaN(parseFloat(value))
            },
            persistent: {
                default: false
            }
        },
        template: '#component-cookies-dialog',
        data: function () {
            return {dialogModel: false};
        },
        created: function () {
            this.dialogModel = this.isExpired;
        },
        computed: {
            isExpired: function () {
                return _.now() - preferences.cookiesAccepted() > _.multiply(this.expirationDays, 24 * 60 * 60 * 1000);
            }
        },
        methods: {
            accept: function () {
                // save to local storage
                preferences.cookiesAccepted(true);
                this.dialogModel = false;
            },
            close: function () {
                this.dialogModel = false;
            }
        }
    });

})(window, _, Vue, GeckoClient);

(function (window, _, Vue, CoinGecko, GeckoClient) {
    'use strict';

    const __ = GeckoClient.__;
    const currencyChartOptions = GeckoClient.getOptions('currency-chart');

    Vue.component('gc-currency-chart', {
        props: ['currencyId'],
        template: '#component-currency-chart',
        data: function () {
            return {
                chartId: 'currency-chart-' + Math.random().toString(36).slice(2),
                series: currencyChartOptions.series,
                selectedSeries: currencyChartOptions.defaultSeries,
                intervals: currencyChartOptions.intervals,
                selectedInterval: currencyChartOptions.defaultInterval,
                chart: null,
                cache: new Map(),
                loading: false,
                error: false,
                errorMessage: '',
                meta: null,
                chartSummary: '',
                summaryStats: [],
                requestSeq: 0
            }
        },
        computed: {
            chartSummaryId: function () {
                return this.chartId + '-summary';
            },
            chartAriaLabel: function () {
                const series = this.selectedSeriesOption();
                return (series ? series.text : __('Market')) + ' market chart';
            },
            freshnessStatus: function () {
                return _.get(this.meta, 'freshness.cache_status', null);
            },
            isStale: function () {
                return ['stale', 'expired', 'fallback'].indexOf(this.freshnessStatus) >= 0;
            },
            freshnessLabel: function () {
                if (!this.meta) return '';

                const status = this.freshnessStatus || 'fresh';
                const label = ['pass', 'hit', 'miss', 'fresh'].indexOf(status) >= 0 ? 'Fresh' : _.startCase(status);
                const timestamp = _.get(this.meta, 'freshness.last_updated_at')
                    || _.get(this.meta, 'freshness.fetched_at');

                return timestamp ? label + ' ' + this.relativeTime(timestamp) : label;
            }
        },
        mounted: function () {
            this.updateChart();
        },
        destroyed: function () {
            this.disposeChart();
        },
        watch: {
            '$root.theme': function () {
                this.updateChart();
            },
            '$root.vsCurrencyId': function () {
                this.cache.clear();
                this.updateChart();
            },
            '$parent.inTransition': function (inTransition) {
                // fixes tab sliding issue
                if (!inTransition && this.$parent.isActive) this.$nextTick(() => this.resize());
            }
        },
        methods: {
            selectedSeriesOption: function () {
                return _.find(this.series, ['value', this.selectedSeries]) || _.first(this.series);
            },
            getCacheKey: function () {
                return [this.currencyId, this.$root.vsCurrencyId, this.selectedInterval].join('_');
            },
            getChartPath: function () {
                return 'coins/' + this.currencyId + '/market_chart';
            },
            chartConfig: function () {
                return {
                    params: {
                        vs_currency: this.$root.vsCurrencyId,
                        days: this.selectedInterval
                    }
                };
            },
            fetchChartData: function (key) {
                const config = this.chartConfig();
                return CoinGecko.coinMarketChart(this.currencyId, config.params)
                    .then(raw => {
                        const entry = {
                            raw: raw,
                            meta: CoinGecko.metaGet(this.getChartPath(), config) || null
                        };

                        this.cache.set(key, entry);

                        return entry;
                    });
            },
            disposeChart: function () {
                if (this.chart) {
                    this.chart.dispose();
                    this.chart = null;
                }
            },
            ensureDominanceBaseline: function () {
                if (this.selectedSeries !== 'dominance' || this.$root.totalMarketCap) {
                    return Promise.resolve();
                }

                return CoinGecko.global()
                    .then(global => {
                        this.$root.global = global;
                    })
                    .catch(() => {});
            },
            normalizeChartData: function (entry) {
                const raw = entry.raw || {};
                const prices = raw.prices || [];
                const marketCaps = raw.market_caps || [];
                const volumes = raw.total_volumes || [];
                const priceValues = prices.map(point => point[1]);
                const marketCapValues = marketCaps.map(point => point[1]);

                return {
                    date: prices.map(point => point[0]),
                    price: priceValues,
                    marketCap: marketCapValues,
                    volume: volumes.map(point => point[1]),
                    dominance: this.dominanceSeries(marketCapValues),
                    relativePerformance: this.relativePerformanceSeries(priceValues),
                    meta: entry.meta || null
                };
            },
            dominanceSeries: function (marketCaps) {
                const totalMarketCap = parseFloat(this.$root.totalMarketCap);
                if (!_.isFinite(totalMarketCap) || totalMarketCap <= 0) {
                    return marketCaps.map(() => null);
                }

                return marketCaps.map(value => {
                    value = parseFloat(value);
                    return _.isFinite(value) ? value / totalMarketCap * 100 : null;
                });
            },
            relativePerformanceSeries: function (prices) {
                const first = _.find(prices, value => {
                    value = parseFloat(value);
                    return _.isFinite(value) && value > 0;
                });
                const base = parseFloat(first);

                if (!_.isFinite(base) || base <= 0) {
                    return prices.map(() => null);
                }

                return prices.map(value => {
                    value = parseFloat(value);
                    return _.isFinite(value) ? (value / base - 1) * 100 : null;
                });
            },
            themeColors: function () {
                const styles = window.getComputedStyle(document.documentElement);
                const read = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;

                return {
                    primary: read('--tbc-brand-ton', '#1BB2DA'),
                    info: read('--tbc-info', '#2F80ED'),
                    muted: read('--tbc-text-secondary', '#4C6178'),
                    up: read('--tbc-market-up', '#12A978'),
                    down: read('--tbc-market-down', '#D84A4A')
                };
            },
            seriesDefinition: function () {
                const colors = this.themeColors();
                const relativeValues = this.currentSeriesValues('relativePerformance');
                const relativeLast = parseFloat(_.last(relativeValues));

                const definitions = {
                    price: {
                        key: 'price',
                        name: __('Price'),
                        type: 'line',
                        color: colors.primary
                    },
                    marketCap: {
                        key: 'marketCap',
                        name: __('Market Cap'),
                        type: 'line',
                        color: colors.info
                    },
                    volume: {
                        key: 'volume',
                        name: __('Volume'),
                        type: 'bar',
                        color: colors.muted
                    },
                    dominance: {
                        key: 'dominance',
                        name: __('Dominance'),
                        type: 'line',
                        color: colors.primary
                    },
                    relativePerformance: {
                        key: 'relativePerformance',
                        name: __('Relative performance'),
                        type: 'line',
                        color: _.isFinite(relativeLast) && relativeLast < 0 ? colors.down : colors.up
                    }
                };

                return definitions[this.selectedSeries] || definitions.price;
            },
            currentSeriesValues: function (key) {
                const entry = this.cache.get(this.getCacheKey());
                if (!entry) return [];
                return this.normalizeChartData(entry)[key] || [];
            },
            formatSeriesValue: function (value, key) {
                value = parseFloat(value);
                if (!_.isFinite(value)) return 'N/A';

                switch (key) {
                    case 'marketCap': return this.$root.marketCapFormat(value);
                    case 'volume': return this.$root.volumeFormat(value);
                    case 'dominance': return this.$root.dominanceFormat(value);
                    case 'relativePerformance': return this.$root.changeFormat(value);
                    case 'price':
                    default: return this.$root.priceFormat(value);
                }
            },
            axisValueFormat: function (value, key) {
                if (key === 'dominance') return this.$root.dominanceFormat(value);
                if (key === 'relativePerformance') return this.$root.changeFormat(value);
                return this.$root.chartYAxisValueFormat(value);
            },
            usableValues: function (values) {
                return values
                    .map(value => parseFloat(value))
                    .filter(value => _.isFinite(value));
            },
            updateSummary: function (data, definition) {
                const values = this.usableValues(data[definition.key] || []);
                if (!values.length) {
                    this.summaryStats = [];
                    this.chartSummary = definition.name + ' chart has no available data for the selected range.';
                    return;
                }

                const start = values[0];
                const end = values[values.length - 1];
                const high = Math.max.apply(null, values);
                const low = Math.min.apply(null, values);
                const rangeLabel = (this.selectedSeriesOption() || {}).text || definition.name;

                this.summaryStats = [
                    {label: 'Start', value: this.formatSeriesValue(start, definition.key)},
                    {label: 'End', value: this.formatSeriesValue(end, definition.key)},
                    {label: 'High', value: this.formatSeriesValue(high, definition.key)},
                    {label: 'Low', value: this.formatSeriesValue(low, definition.key)}
                ];
                this.chartSummary = rangeLabel + ' chart for ' + this.currencyId + ' over ' + this.selectedInterval + ' days. '
                    + 'Start ' + this.formatSeriesValue(start, definition.key) + ', end ' + this.formatSeriesValue(end, definition.key)
                    + ', high ' + this.formatSeriesValue(high, definition.key) + ', low ' + this.formatSeriesValue(low, definition.key) + '.';
            },
            buildOptions: function (data, definition) {
                const colors = this.themeColors();
                const options = _.cloneDeep(currencyChartOptions.echartOptions);
                const secondaryKey = definition.key === 'volume' ? 'price' : 'volume';
                const secondaryName = definition.key === 'volume' ? __('Price') : __('Volume');
                const compact = window.matchMedia && window.matchMedia('(max-width: 599px)').matches;

                options.tooltip = options.tooltip || {};
                options.xAxis[0].data  = data.date;
                options.xAxis[1].data  = data.date;

                if (compact) {
                    options.grid[0].bottom = 150;
                    options.grid[1].height = 54;
                    options.grid[1].bottom = 66;
                    options.dataZoom[1].bottom = 12;
                }

                options.series[0].id = definition.key;
                options.series[0].name = definition.name;
                options.series[0].type = definition.type;
                options.series[0].data = data[definition.key];
                options.series[0].itemStyle = options.series[0].itemStyle || {};
                options.series[0].itemStyle.color = definition.color;
                options.series[0].lineStyle = options.series[0].lineStyle || {};
                options.series[0].lineStyle.color = definition.color;
                if (definition.type === 'bar') {
                    options.series[0].barMaxWidth = 18;
                }

                options.series[1].id = secondaryKey;
                options.series[1].type = secondaryKey === 'volume' ? 'bar' : 'line';
                options.series[1].name = secondaryName;
                options.series[1].data = data[secondaryKey];
                options.series[1].itemStyle = options.series[1].itemStyle || {};
                options.series[1].itemStyle.color = secondaryKey === 'volume' ? colors.muted : colors.primary;
                options.series[1].lineStyle = options.series[1].lineStyle || {};
                options.series[1].lineStyle.color = colors.primary;
                options.series[1].showSymbol = false;

                options.tooltip.formatter = (params) => {
                    let html = '';
                    html += this.$root.chartTooltipDateFormat(params[0].axisValue);
                    html += '<br>';
                    html += _.map(params, point => {
                        const key = point.seriesId || definition.key;
                        return point.marker + ' ' + point.seriesName + ': ' + this.formatSeriesValue(point.value, key);
                    }).join('<br>');
                    return html;
                };

                options.yAxis[0].axisLabel = options.yAxis[0].axisLabel || {};
                options.yAxis[0].axisLabel.hideOverlap = true;
                options.yAxis[0].axisLabel.formatter = value => this.axisValueFormat(value, definition.key);
                options.yAxis[0].splitNumber = compact ? 3 : 5;

                options.xAxis[0].axisLabel = options.xAxis[0].axisLabel || {};
                options.xAxis[0].axisLabel.hideOverlap = true;
                options.xAxis[0].axisLabel.showMinLabel = !compact;
                options.xAxis[0].axisLabel.showMaxLabel = !compact;
                options.xAxis[0].axisLabel.formatter = value => this.$root.chartXAxisDateFormat(value, this.selectedInterval);

                options.xAxis[1].axisLabel = options.xAxis[1].axisLabel || {};
                options.xAxis[1].axisLabel.formatter = value => this.$root.chartXAxisDateFormat(value, this.selectedInterval);

                options.axisPointer.label.formatter = params => {
                    return params.axisDimension === 'y'
                        ? this.axisValueFormat(params.value, definition.key)
                        : this.$root.chartXAxisDateFormat(params.value, this.selectedInterval);
                };

                return options;
            },
            initChart: function (entry, echartsInstance) {
                const data = this.normalizeChartData(entry);
                const definition = this.seriesDefinition();
                const values = this.usableValues(data[definition.key] || []);

                if (!values.length) {
                    throw new Error('No chart data available for ' + definition.key);
                }

                this.meta = data.meta;
                this.updateSummary(data, definition);
                const options = this.buildOptions(data, definition);

                this.$nextTick(() => {
                    try {
                        this.disposeChart();
                        this.chart = echartsInstance.init(this.$refs.chartContainer, this.$root.darkTheme ? 'dark' : undefined);
                        this.chart.setOption(options);
                        this.chart.dispatchAction({
                            type: 'dataZoom',
                            start: 0,
                            end: 100
                        });
                        this.resize();
                    } catch (err) {
                        this.handleChartError(err);
                    }
                });
            },
            handleChartError: function (err) {
                this.disposeChart();
                this.loading = false;
                this.error = true;
                this.errorMessage = __('Market chart is unavailable. Coin details remain available.');
                this.chartSummary = this.errorMessage;
                this.summaryStats = [];

                if (_.get(GeckoClient, 'runtime.observability.verboseTracing') && window.console) {
                    window.console.warn(err);
                }
            },
            updateChart: function () {
                const requestId = ++this.requestSeq;
                const key = this.getCacheKey();
                const entryPromise = this.cache.has(key)
                    ? Promise.resolve(this.cache.get(key))
                    : this.fetchChartData(key);

                this.disposeChart();
                this.loading = true;
                this.error = false;
                this.errorMessage = '';

                return Promise.all([
                    GeckoClient.loadECharts(),
                    entryPromise,
                    this.ensureDominanceBaseline()
                ])
                    .then(result => {
                        if (requestId !== this.requestSeq) return;
                        this.loading = false;
                        this.initChart(result[1], result[0]);
                    })
                    .catch(err => {
                        if (requestId !== this.requestSeq) return;
                        this.handleChartError(err);
                    });
            },
            resize() {
                if (this.chart) this.chart.resize()
            },
            relativeTime: function (timestamp) {
                const date = new Date(timestamp);
                if (!GeckoClient.utils.isValidDate(date)) return '';

                const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
                if (seconds < 60) return 'now';
                if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                return Math.floor(seconds / 86400) + 'd ago';
            }
        }

    });



})(window, _, Vue, CoinGecko, GeckoClient);

(function (window, _, Vue, GeckoClient) {
    'use strict';

    const formats = GeckoClient.formats;

    Vue.component('gc-currency-converter', {
        props: {
            baseSymbol: {},
            baseValue: {
                default: 1,
            },
            quoteSymbol: {},
            quoteValue: {
                default: null,
            },
            rate: {},
            buy: {
                default: true
            },
            buyHref: {},
            sell: {
                default: true
            },
            sellHref: {}
        },
        template: '#component-currency-converter',
        data: function () {
            return {
                baseModel: null,
                quoteModel: null,
                formatter: Intl.NumberFormat(formats.converter.locale, formats.converter.options)
            }
        },
        computed: {
            isValid: function () {
                return this.baseSymbol && this.quoteSymbol && this.rate;
            }
        },
        created: function () {
            // set initial values

            const quoteValue = parseFloat(this.quoteValue);
            if (_.isFinite(quoteValue)) {
                this.quoteModel = quoteValue;
                this.quoteUpdated();
                return;
            }

            const baseValue = parseFloat(this.baseValue);
            if (_.isFinite(baseValue)) {
                this.baseModel = baseValue;
                this.baseUpdated();
            }
        },
        methods: {
            // used for name prop to avoid autocompletes
            randomName: function () {
                return 'input-' + Math.random().toString(16).substr(2);
            },
            baseUpdated: function () {
                const value = parseFloat(this.baseModel) * this.rate;
                this.quoteModel = _.isFinite(value) ? this.formatter.format(value) : null;
            },
            quoteUpdated: function () {
                const value = parseFloat(this.quoteModel) / this.rate;
                this.baseModel = _.isFinite(value) ? this.formatter.format(value) : null;
            }
        }
    });

})(window, _, Vue, GeckoClient);
(function (window, document, _, Vue, GeckoClient) {
    'use strict';

    const currencyOptions = GeckoClient.getOptions('currency', {});
    const widgetOptions = currencyOptions.exchangeWidget || {};
    const defaultWidgetUrl = 'https://changenow.io/embeds/exchange-widget/v2/widget.html';
    const defaultConnectorScriptUrl = 'https://changenow.io/embeds/exchange-widget/v2/stepper-connector.js';

    function normalizeKey(value) {
        return _.toLower(_.trim(value || ''));
    }

    function normalizeColor(value, fallback) {
        value = _.trim(value || fallback || '').replace(/^#/, '');
        return /^[0-9a-f]{6}$/i.test(value) ? _.toLower(value) : fallback;
    }

    function getSupportedAsset(currency) {
        const supportedAssets = widgetOptions.supportedAssets || {};
        const ids = supportedAssets.ids || {};
        const symbols = supportedAssets.symbols || {};
        const id = normalizeKey(_.get(currency, 'id'));
        const symbol = normalizeKey(_.get(currency, 'symbol'));
        const asset = ids[id] || symbols[symbol] || null;

        if (asset) {
            return _.isString(asset) ? {from: asset} : _.cloneDeep(asset);
        }

        if (widgetOptions.symbolFallback && symbol) {
            return {from: symbol};
        }

        return null;
    }

    function buildWidgetUrl(baseUrl, params) {
        const url = new URL(baseUrl || defaultWidgetUrl, window.location.href);

        _.forOwn(params, (value, key) => {
            url.searchParams.set(key, value === null || value === undefined ? '' : String(value));
        });

        return url.toString();
    }

    Vue.component('gc-currency-exchange-widget', {
        props: {
            currency: {}
        },
        template: '#component-currency-exchange-widget',
        computed: {
            providerName: function () {
                return widgetOptions.provider || 'ChangeNOW';
            },
            isEnabled: function () {
                return widgetOptions.enabled === true && !!this.linkId;
            },
            linkId: function () {
                return _.trim(widgetOptions.linkId || '');
            },
            supportedAsset: function () {
                return getSupportedAsset(this.currency);
            },
            widgetStatus: function () {
                if (!this.isEnabled) return 'disabled';
                if (!this.currency) return 'loading';
                return this.supportedAsset ? 'ready' : 'unsupported';
            },
            isReady: function () {
                return this.widgetStatus === 'ready';
            },
            listingUrl: function () {
                return _.trim(widgetOptions.listingUrl || '');
            },
            assetLabel: function () {
                return this.supportedAsset && this.supportedAsset.label
                    ? this.supportedAsset.label
                    : _.get(this.currency, 'name', this.providerName);
            },
            targetLabel: function () {
                return this.supportedAsset && this.supportedAsset.toLabel
                    ? this.supportedAsset.toLabel
                    : 'USDT on TON';
            },
            iframeTitle: function () {
                return this.providerName + ' exchange widget for ' + this.assetLabel;
            },
            iframeSrc: function () {
                if (!this.isReady) return null;

                const defaults = widgetOptions.defaults || {};
                const target = this.supportedAsset.to || defaults.to || 'usdtton';
                const params = Object.assign({}, defaults, {
                    from: this.supportedAsset.from,
                    to: target,
                    link_id: this.linkId,
                    primaryColor: normalizeColor(defaults.primaryColor, '1bb2da'),
                    backgroundColor: normalizeColor(defaults.backgroundColor, 'f6fafd')
                });

                return buildWidgetUrl(widgetOptions.widgetUrl, params);
            }
        },
        mounted: function () {
            if (this.isReady) {
                this.ensureConnectorScript();
            }
        },
        updated: function () {
            if (this.isReady) {
                this.ensureConnectorScript();
            }
        },
        methods: {
            ensureConnectorScript: function () {
                const scriptUrl = widgetOptions.connectorScriptUrl || defaultConnectorScriptUrl;
                if (!scriptUrl || document.querySelector('script[data-tbc-changenow-connector="true"]')) {
                    return;
                }

                const script = document.createElement('script');
                script.src = scriptUrl;
                script.defer = true;
                script.setAttribute('data-tbc-changenow-connector', 'true');
                document.body.appendChild(script);
            }
        }
    });

})(window, document, _, Vue, GeckoClient);

(function (window, Vue) {
    'use strict';

    Vue.component('gc-disclaimer-message', {
        template: '#component-disclaimer-message'
    });

})(window, Vue);

(function (window, _, Vue, CoinGecko, GeckoClient) {
    'use strict';

    const utils = GeckoClient.utils;

    const exchangeOptions = GeckoClient.getOptions('exchange');

    Vue.component('gc-exchange-chart', {
        props: ['exchange-id'],
        template: '#component-exchange-chart',
        data: function () {
            return {
                chart: null,
                loading: false,
                selectedInterval: exchangeOptions.defaultInterval,
                intervals: exchangeOptions.intervals
            }
        },
        mounted: function () {
            this.updateChart();
        },
        destroyed: function () {
            if (this.chart) this.chart.dispose();
        },
        watch: {
            '$root.theme': function () {
                // rebuild chart for theme
                this.updateChart();
            }
        },
        methods: {
            fetchVolumeData: function (days) {
                const params = {days: days};
                return CoinGecko.exchangeVolumeChart(this.exchangeId, params);
            },
            initChart: function (data, echartsInstance) {
                const theme = this.$root.darkTheme ? 'dark' : undefined;

                const options = _.cloneDeep(exchangeOptions.echartOptions);
                options.tooltip = options.tooltip  || {};
                // tooltip box content build function
                options.tooltip.formatter = (params) => {
                    const value = this.$root.volumeBTCFormat(params[0].value[1]);
                    let html = '';
                    html += this.$root.chartTooltipDateFormat(params[0].axisValue);
                    html += '<br>';
                    html += params[0].marker + ' ' + params[0].seriesName + ': ' + value;
                    return html;
                };
                options.dataset = {source: data};

                // wait for this.$refs.chartContainer to be available
                this.$nextTick(() => {
                    this.chart = echartsInstance.init(this.$refs.chartContainer, theme);
                    this.chart.setOption(options);
                });
            },
            updateChart: function () {
                if (this.chart) this.chart.dispose();

                // fetch remote data
                this.loading = true;
                Promise.all([
                    GeckoClient.loadECharts(),
                    this.fetchVolumeData(this.selectedInterval)
                ])
                    .then(result => {
                        this.loading = false;
                        this.initChart(result[1], result[0]);
                    })
                    .catch(() => this.loading = false);
            },
            resize() {
                if (this.chart) this.chart.resize()
            }
        }

    });



})(window, _, Vue, CoinGecko, GeckoClient);

(function (window, Vue) {
    'use strict';

    Vue.component('gc-page-loader', {
        props: ['loading'],
        template: '#component-page-loader'
    });

})(window, Vue);

(function (window, document, _, axios, Vue, GeckoClient) {
    'use strict';

    const __ = GeckoClient.__;
    const resultGroups = {
        recent: __( 'Recent searches' ),
        action: __( 'Quick actions' ),
        coin: __( 'Coins' ),
        ton_asset: __( 'TON assets' ),
        exchange: __( 'Exchanges' ),
        category: __( 'Categories' )
    };
    const recentLimit = 5;

    Vue.component('gc-search-bar', {
        template: '#component-search-bar',
        data: function () {
            return {
                model: null,
                search: null,
                loading: false,
                hasError: false,
                items: [],
                recent: [],
                requestCounter: 0,
                searchOpened: false,
                searchTimer: null,
                dialog: false,
                menuProps: {
                    contentClass: 'gc-search-menu',
                    maxHeight: 420,
                    offsetY: true,
                    closeOnClick: false
                },
                dialogMenuProps: {
                    contentClass: 'gc-search-menu gc-search-dialog-menu',
                    maxHeight: 560,
                    offsetY: true,
                    closeOnClick: false
                },
                watchlistIds: [],
                watchlistUnsubscribe: null
            };
        },
        computed: {
            compactSearch: function () {
                return _.get(GeckoClient, 'telegram.active') || _.get(this.$vuetify, 'breakpoint.xs', window.innerWidth < 600);
            },
            noDataText: function () {
                if (this.loading) return __( 'Searching...' );
                if (this.hasError) return __( 'Search is unavailable. Try again.' );
                if (this.getQueryText() === '') return __( 'Start typing or choose a quick action.' );
                return __( 'No results found.' );
            }
        },
        created: function () {
            this.initWatchlist();
        },
        watch: {
            model: function (selected) {
                this.selectResult(selected);
            }
        },
        mounted: function () {
            this.loadRecentSearches();
            window.addEventListener('keydown', this.onGlobalKeydown);
        },
        beforeDestroy: function () {
            window.removeEventListener('keydown', this.onGlobalKeydown);
            if (this.searchTimer) window.clearTimeout(this.searchTimer);
            if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
        },
        methods: {
            avatarChar: function (name) {
                return _.toUpper(_.first(name || '')) || '?';
            },
            getQueryText: function () {
                return _.toLower(_.trim(this.search || ''));
            },
            searchEndpoint: function () {
                return _.get(GeckoClient, 'search.apiBaseUrl', '/api/search');
            },
            searchSurface: function () {
                return _.get(GeckoClient, 'analytics.surface', () => 'public_web')();
            },
            initWatchlist: function () {
                const watchlist = GeckoClient.watchlist;
                if (!watchlist) return;

                this.watchlistUnsubscribe = watchlist.onChange(() => this.syncWatchlistIds());
                watchlist.init().then(() => this.syncWatchlistIds());
            },
            syncWatchlistIds: function () {
                this.watchlistIds = GeckoClient.watchlist ? GeckoClient.watchlist.ids() : [];
            },
            isWatchlistResult: function (item) {
                return item && (item.type === 'coin' || item.coin_id);
            },
            isWatched: function (item) {
                const id = item.coin_id || item.id;
                return this.watchlistIds.indexOf(id) >= 0;
            },
            watchlistIcon: function (item) {
                return this.isWatched(item) ? 'mdi-star' : 'mdi-star-outline';
            },
            watchlistLabel: function (item) {
                const name = item.name || item.title || item.id || __( 'asset' );
                return (this.isWatched(item) ? 'Remove ' : 'Add ') + name + ' ' + (this.isWatched(item) ? 'from' : 'to') + ' Watchlist';
            },
            toggleWatchlist: function (item) {
                if (!GeckoClient.watchlist) return;

                const entry = {
                    id: item.coin_id || item.id,
                    symbol: item.symbol,
                    name: item.name || item.title,
                    image: item.large || item.small || item.thumb || item.image
                };

                GeckoClient.watchlist.toggle(entry, {sourceRoute: 'search'})
                    .then(() => this.syncWatchlistIds());
            },
            recentStorageKey: function () {
                const prefix = _.get(GeckoClient, 'preferences.prefix', 'GeckoClient:');
                return prefix + 'recent_searches';
            },
            resultKey: function (item) {
                if (!item) return '';
                return item.originalSearchId || item.searchId || ((item.type || 'result') + ':' + item.id);
            },
            isResultItem: function (item) {
                return !!(item && !item.header && item.route && item.id && this.resultKey(item));
            },
            normalizeResult: function (item) {
                if (!item || item.header) return item;

                const result = Object.assign({}, item);
                result.type = result.type || 'action';
                result.name = result.name || result.title || result.id || '';
                result.title = result.title || result.name;
                result.subtitle = result.subtitle || result.symbol || this.resultTypeLabel(result);
                result.searchId = result.searchId || (result.type + ':' + result.id);

                return result;
            },
            decoratedRecentSearch: function (item) {
                const result = this.normalizeResult(_.cloneDeep(item));
                const key = this.resultKey(result);
                result.originalSearchId = key;
                result.searchId = 'recent:' + key;
                result.recent = true;
                return result;
            },
            loadRecentSearches: function () {
                let parsed = [];
                try {
                    parsed = JSON.parse(localStorage.getItem(this.recentStorageKey()) || '[]');
                } catch (err) {
                    parsed = [];
                }

                this.recent = (Array.isArray(parsed) ? parsed : [])
                    .map(item => this.normalizeResult(item))
                    .filter(item => this.isResultItem(item))
                    .slice(0, recentLimit);
            },
            saveRecentSearches: function () {
                try {
                    localStorage.setItem(this.recentStorageKey(), JSON.stringify(this.recent.slice(0, recentLimit)));
                } catch (err) {
                    // Storage can be unavailable in private or constrained webviews.
                }
            },
            rememberSearchResult: function (selected) {
                if (!this.isResultItem(selected)) return;

                const result = this.normalizeResult(selected);
                const key = this.resultKey(result);
                const stored = _.cloneDeep(
                    _.pick(result, [
                        'searchId',
                        'type',
                        'id',
                        'coin_id',
                        'exchange_id',
                        'category_id',
                        'title',
                        'name',
                        'subtitle',
                        'symbol',
                        'rank',
                        'large',
                        'image',
                        'tags',
                        'contract_addresses',
                        'route',
                        'links',
                        'analytics'
                    ])
                );
                stored.searchId = key;

                this.recent = [stored]
                    .concat(this.recent.filter(item => this.resultKey(item) !== key))
                    .slice(0, recentLimit);
                this.saveRecentSearches();
            },
            setItems: function (results) {
                const query = this.getQueryText();
                const items = [];
                const seenTypes = {};
                const seenResults = {};

                if (query === '' && this.recent.length) {
                    items.push({header: resultGroups.recent});
                    this.recent.forEach(item => {
                        const recent = this.decoratedRecentSearch(item);
                        seenResults[this.resultKey(recent)] = true;
                        items.push(recent);
                    });
                }

                (results || []).map(item => this.normalizeResult(item)).forEach(item => {
                    if (!this.isResultItem(item)) return;

                    const key = this.resultKey(item);
                    if (seenResults[key]) return;
                    seenResults[key] = true;

                    const type = item.type || 'action';
                    if (!seenTypes[type]) {
                        seenTypes[type] = true;
                        items.push({header: resultGroups[type] || type});
                    }

                    items.push(item);
                });

                this.items = items;
                this.openSearchMenu();
            },
            searchParams: function () {
                return {
                    q: this.getQueryText(),
                    limit: _.get(GeckoClient, 'search.defaultLimit', 12),
                    surface: this.searchSurface()
                };
            },
            fetchData: function (requestId) {
                return axios.get(this.searchEndpoint(), {params: this.searchParams()})
                    .then(response => {
                        if (requestId !== this.requestCounter) return;

                        const payload = response.data || {};
                        const data = payload.ok === true ? payload.data : payload;
                        this.hasError = false;
                        this.setItems(_.get(data, 'results', []));
                    })
                    .catch(() => {
                        if (requestId === this.requestCounter) {
                            this.hasError = true;
                            this.setItems([]);
                        }
                    })
                    .finally(() => {
                        if (requestId === this.requestCounter) {
                            this.loading = false;
                        }
                    });
            },
            searchItems: function () {
                const requestId = ++this.requestCounter;
                this.loading = true;
                this.hasError = false;

                if (this.getQueryText() === '') {
                    this.setItems([]);
                }

                return this.fetchData(requestId);
            },
            queueSearchItems: function (value) {
                if (value !== undefined) {
                    this.search = value;
                }

                if (this.searchTimer) window.clearTimeout(this.searchTimer);
                this.searchTimer = window.setTimeout(() => this.searchItems(), this.getQueryText() === '' ? 0 : 160);
            },
            clearSearch: function () {
                this.search = null;
                this.queueSearchItems('');
            },
            openSearch: function (trigger) {
                this.trackSearchOpened(trigger || 'button');

                if (this.compactSearch) {
                    this.dialog = true;
                    this.$nextTick(() => {
                        this.focusSearchRef('dialogSearch');
                        this.searchItems();
                    });
                    return;
                }

                this.$nextTick(() => {
                    this.focusSearchRef('inlineSearch');
                    this.searchItems();
                });
            },
            closeSearch: function () {
                this.dialog = false;
                this.searchOpened = false;
            },
            focusSearchRef: function (refName) {
                const ref = this.$refs[refName];
                const root = ref && ref.$el ? ref.$el : ref;
                const input = root && root.querySelector ? root.querySelector('input') : null;
                if (!input) return;

                input.focus();
                input.select();
                this.openSearchMenu(refName);
            },
            openSearchMenu: function (refName) {
                this.$nextTick(() => {
                    const name = refName || (this.dialog ? 'dialogSearch' : 'inlineSearch');
                    const ref = this.$refs[name];
                    const root = ref && ref.$el ? ref.$el : ref;
                    const input = root && root.querySelector ? root.querySelector('input') : null;
                    if (ref && (this.dialog || !input || document.activeElement === input)) ref.isMenuActive = true;
                });
            },
            editableTarget: function (target) {
                const tag = target && target.tagName ? target.tagName.toLowerCase() : '';
                return ['input', 'textarea', 'select'].indexOf(tag) !== -1 || !!(target && target.isContentEditable);
            },
            onGlobalKeydown: function (event) {
                const key = _.toLower(event.key || '');
                const commandShortcut = (event.ctrlKey || event.metaKey) && key === 'k';
                const slashShortcut = key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey && !this.editableTarget(event.target);

                if (!commandShortcut && !slashShortcut) return;

                event.preventDefault();
                this.openSearch(commandShortcut ? 'shortcut' : 'slash');
            },
            selectResult: function (selected) {
                if (!this.isResultItem(selected)) return;

                this.trackSearchSelection(selected);
                this.rememberSearchResult(selected);

                const route = selected.route || {};
                let next = null;
                if (route.name) {
                    next = {
                        name: route.name,
                        params: route.params || {},
                        query: route.query || {}
                    };
                } else if (route.path) {
                    next = route.path;
                }
                if (!next) return;

                const resolved = this.$router.resolve(next).route;
                this.model = null;
                this.closeSearch();
                this.search = null;
                this.setItems([]);

                if (resolved.fullPath === this.$route.fullPath) return;
                this.$router.push(next).catch(() => {});
            },
            trackSearchOpened: function (trigger) {
                if (!GeckoClient.analytics || this.searchOpened) return;

                this.searchOpened = true;
                GeckoClient.analytics.emit('search_opened', {
                    trigger: trigger || 'focus',
                    query_present: this.getQueryText() !== '',
                    surface: this.searchSurface()
                });
            },
            trackSearchSelection: function (selected) {
                if (!GeckoClient.analytics) return;

                const analytics = Object.assign(
                    {},
                    selected.analytics || {},
                    {
                        event_name: 'search_result_selected',
                        result_type: selected.type,
                        coin_id: selected.coin_id || (selected.type === 'coin' ? selected.id : null),
                        exchange_id: selected.exchange_id || (selected.type === 'exchange' ? selected.id : null),
                        category_id: selected.category_id || (selected.type === 'category' ? selected.id : null),
                        rank: selected.rank,
                        query_length_bucket: _.get(selected, 'analytics.query_length_bucket') || GeckoClient.analytics.queryLengthBucket(this.search),
                        surface: this.searchSurface()
                    }
                );

                GeckoClient.analytics.emit(analytics.event_name, analytics);
            },
            onFocus: function () {
                this.trackSearchOpened('focus');
                this.searchItems();
            },
            resultTypeLabel: function (item) {
                if (!item) return '';
                if (item.recent) return __( 'Recent' );
                if (item.type === 'coin') return __( 'Coin' );
                if (item.type === 'ton_asset') return __( 'TON asset' );
                if (item.type === 'exchange') return __( 'Exchange' );
                if (item.type === 'category') return __( 'Category' );
                return __( 'Action' );
            },
            resultIcon: function (item) {
                if (!item) return 'mdi-magnify';
                if (item.recent) return 'mdi-history';
                if (_.get(item, 'route.name') === 'crypto-exchange') return 'mdi-swap-horizontal-circle-outline';
                if (item.type === 'ton_asset') return 'mdi-diamond-stone';
                if (item.type === 'exchange') return 'mdi-bank-outline';
                if (item.type === 'category') return 'mdi-tag-outline';
                if (item.type === 'action') return 'mdi-lightning-bolt-outline';
                return 'mdi-currency-btc';
            },
            itemImage: function (item) {
                return item && (item.large || item.image);
            }
        }
    });

})(window, document, _, axios, Vue, GeckoClient);

(function (_, Vue, GeckoClient) {
    'use strict';

    Vue.component('gc-share-card', {
        template: '#component-share-card',
        props: {
            card: {
                type: Object,
                default: null
            },
            dense: {
                type: Boolean,
                default: false
            }
        },
        data: function () {
            return {
                sharing: false,
                copied: false
            };
        },
        computed: {
            normalizedCard: function () {
                if (!GeckoClient.share) return this.card || {};
                return GeckoClient.share.normalizeCard(this.card || {});
            },
            metrics: function () {
                return _.isArray(this.normalizedCard.metrics) ? this.normalizedCard.metrics : [];
            },
            shareLabel: function () {
                return 'Share ' + (this.normalizedCard.title || 'market card');
            }
        },
        methods: {
            shareCard: function () {
                if (!GeckoClient.share || this.sharing) return;

                this.sharing = true;
                this.copied = false;
                GeckoClient.share.share(this.normalizedCard)
                    .then(shared => {
                        this.copied = shared === true;
                        this.$emit('shared', this.normalizedCard);
                    })
                    .finally(() => this.sharing = false);
            }
        }
    });

})(_, Vue, GeckoClient);

(function (window, _, Vue) {
    'use strict';

    const supportedSymbols = {
        ada: 'cardano',
        bnb: 'binance-coin',
        btc: 'bitcoin',
        busd: 'binance-usd',
        doge: 'dogecoin',
        dot: 'polkadot',
        eth: 'ethereum',
        ton: 'the-open-network',
        usdc: 'usd-coin',
        usdt: 'tether',
        xrp: 'ripple'
    };

    const defaultSymbols = ['btc', 'eth', 'ton'];


    Vue.component('gc-stats-bar', {
        props: {
            dominance: {
                default: () => defaultSymbols
            }
        },
        template: '#component-stats-bar',
        computed: {
            dominanceEntries: function () {
                let entries = [];
                let symbols = [];

                if (_.isArray(this.dominance)) {
                    symbols = this.dominance;
                } else if (_.isString(this.dominance)) {
                    symbols = this.dominance.split(',');
                }

                symbols.forEach(symbol => {
                    symbol = _.toLower(_.trim(symbol));
                    const id = _.get(supportedSymbols, symbol, false);
                    if (id) {
                        entries.push({
                            symbol: symbol,
                            id: id,
                            route: {name: 'currency', params: {id: id}}
                        })
                    }
                })

                return entries;
            }
        }
    });

})(window, _, Vue);

(function (window, Vue, CoinGecko) {
    'use strict';

    Vue.component('gc-trending-coins', {
        template: '#component-trending-coins',
        data: function () {
            return {coins: []};
        },
        created: function () {
            CoinGecko.searchTrending().then(trending => {
                this.coins = _.each(trending.coins, coin => {
                    coin.route = {name: 'currency', params: {id: coin.id}}
                });
            }).catch(error => {
                console.warn('CoinGecko.searchTrending() failed', error);
            });
        }
    });

})(window, Vue, CoinGecko);
(function (window, _, VueRouter, GeckoClient) {
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
        if (GeckoClient.achievements && _.isFunction(GeckoClient.achievements.trackRoute)) {
            GeckoClient.achievements.trackRoute(to);
        }
    })


})(window, _, VueRouter, GeckoClient);

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

(function (window, _, axios, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;
    const options = GeckoClient.getOptions('currencies');
    const route = GeckoClient.routesConfig.currencies;
    const perPage = Math.min(100, options.perPage) || 50;
    const tonEndpoint = options.tonApiBaseUrl || '/api/ton/assets';

    function percentChange(currency) {
        return parseFloat(currency.price_change_percentage_24h_in_currency);
    }

    function hasFiniteChange(currency) {
        return _.isFinite(percentChange(currency));
    }

    GeckoClient.router.addRoute({
        name: 'currencies',
        path: route.path,
        component: {
            template: '#route-currencies',
            data: function () {
                return {
                    global: null,
                    marketCurrencies: [],
                    trendingCoins: [],
                    watchlistIds: [],
                    tonAssets: [],
                    tonMarketCurrencies: [],
                    loadingGlobal: false,
                    loadingMarkets: false,
                    loadingTrending: false,
                    loadingTonCuration: false,
                    loadingTonMarkets: false,
                    globalError: false,
                    marketError: false,
                    trendingError: false,
                    tonCurationError: false,
                    tonMarketError: false,
                    globalMeta: null,
                    marketMeta: null,
                    marketConfig: null,
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                this.initWatchlist();
                this.fetchPulse();
                setTitle(options.title);
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    this.fetchPulse();
                },
                tonAssets: function () {
                    this.fetchTonMarkets();
                }
            },
            computed: {
                loading: function () {
                    return this.loadingGlobal || this.loadingMarkets || this.loadingTrending;
                },
                upstreamError: function () {
                    return this.marketError && !this.marketCurrencies.length;
                },
                partialError: function () {
                    return !this.upstreamError && (this.globalError || this.marketError || this.trendingError);
                },
                freshnessMeta: function () {
                    return this.marketMeta || this.globalMeta || null;
                },
                freshnessStatus: function () {
                    return _.get(this.freshnessMeta, 'freshness.cache_status', null);
                },
                freshnessLabel: function () {
                    const status = this.freshnessStatus || 'fresh';
                    const label = ['pass', 'hit', 'fresh'].indexOf(status) >= 0 ? 'Fresh' : _.startCase(status);
                    const timestamp = _.get(this.freshnessMeta, 'freshness.last_updated_at')
                        || _.get(this.freshnessMeta, 'freshness.fetched_at');

                    return timestamp ? label + ' ' + this.relativeTime(timestamp) : label;
                },
                freshnessColor: function () {
                    return this.isStale ? 'warning' : 'success';
                },
                isStale: function () {
                    return ['stale', 'expired', 'fallback'].indexOf(this.freshnessStatus) >= 0;
                },
                globalStats: function () {
                    return [
                        {
                            label: 'Market cap',
                            icon: 'mdi-finance',
                            value: this.$root.marketCapFormat(_.get(this.global, ['total_market_cap', this.$root.vsCurrencyId], null))
                        },
                        {
                            label: '24h volume',
                            icon: 'mdi-chart-bar',
                            value: this.$root.volumeFormat(_.get(this.global, ['total_volume', this.$root.vsCurrencyId], null))
                        },
                        {
                            label: 'Assets',
                            icon: 'mdi-database',
                            value: this.$root.bigNumberFormat(_.get(this.global, 'active_cryptocurrencies', null))
                        },
                        {
                            label: 'BTC dominance',
                            icon: 'mdi-bitcoin',
                            value: this.$root.dominanceFormat(_.get(this.global, ['market_cap_percentage', 'btc'], null))
                        }
                    ];
                },
                tonAssetCoinIds: function () {
                    return _.uniq((this.tonAssets || []).map(asset => _.toLower(asset && asset.coin_id || '')).filter(Boolean));
                },
                tonCurrencies: function () {
                    const ids = this.tonAssetCoinIds;
                    if (!ids.length) return [];
                    const byId = _.keyBy(this.tonMarketCurrencies, 'id');
                    return ids
                        .map(id => byId[id] || null)
                        .filter(Boolean)
                        .map(currency => this.extendCurrency(Object.assign({}, currency)))
                        .slice(0, 6);
                },
                topGainers: function () {
                    return this.marketCurrencies
                        .filter(currency => hasFiniteChange(currency) && percentChange(currency) > 0)
                        .slice()
                        .sort((a, b) => percentChange(b) - percentChange(a))
                        .slice(0, 4);
                },
                topLosers: function () {
                    return this.marketCurrencies
                        .filter(currency => hasFiniteChange(currency) && percentChange(currency) < 0)
                        .slice()
                        .sort((a, b) => percentChange(a) - percentChange(b))
                        .slice(0, 4);
                },
                watchlistCurrencies: function () {
                    if (!this.watchlistIds.length) return [];

                    return this.marketCurrencies.filter(currency => {
                        return this.watchlistIds.indexOf(currency.id) >= 0 || this.watchlistIds.indexOf(currency.symbol) >= 0;
                    }).slice(0, 4);
                },
                marketInsightContext: function () {
                    if (!this.marketCurrencies.length || !GeckoClient.ai) return null;

                    return {
                        insight_type: 'market_summary',
                        subject: 'Market pulse for ' + _.toUpper(this.$root.vsCurrencyId),
                        market_data_age_seconds: GeckoClient.ai.marketDataAgeSeconds(this.freshnessMeta),
                        market_data_updated_at: GeckoClient.ai.marketDataUpdatedAt(this.freshnessMeta),
                        market_data: {
                            vs_currency: this.$root.vsCurrencyId,
                            freshness_status: this.freshnessStatus || 'fresh',
                            global: {
                                market_cap: _.get(this.global, ['total_market_cap', this.$root.vsCurrencyId], null),
                                volume_24h: _.get(this.global, ['total_volume', this.$root.vsCurrencyId], null),
                                active_cryptocurrencies: _.get(this.global, 'active_cryptocurrencies', null),
                                btc_dominance: _.get(this.global, ['market_cap_percentage', 'btc'], null)
                            },
                            top_gainers: this.topGainers.map(currency => this.aiCurrencySnapshot(currency)),
                            top_losers: this.topLosers.map(currency => this.aiCurrencySnapshot(currency)),
                            ton_assets: this.tonCurrencies.map(currency => this.aiCurrencySnapshot(currency)),
                            watchlist_preview: this.watchlistCurrencies.map(currency => this.aiCurrencySnapshot(currency))
                        }
                    };
                },
                marketPulseShareCard: function () {
                    const topGainer = _.first(this.topGainers);
                    const topLoser = _.first(this.topLosers);
                    const marketCap = _.get(this.global, ['total_market_cap', this.$root.vsCurrencyId], null);
                    const volume = _.get(this.global, ['total_volume', this.$root.vsCurrencyId], null);

                    return {
                        title: 'Market pulse',
                        subtitle: _.toUpper(this.$root.vsCurrencyId) + ' snapshot',
                        body: 'Global market context, TON ecosystem movers, and watchlist previews.',
                        route: _.get(this.$route, 'fullPath') || '/',
                        campaign: 'market-pulse',
                        context: 'market_pulse',
                        freshness: this.freshnessLabel,
                        metrics: [
                            {label: 'Market cap', value: marketCap ? this.$root.marketCapFormat(marketCap) : 'Loading'},
                            {label: '24h volume', value: volume ? this.$root.volumeFormat(volume) : 'Loading'},
                            {label: 'Top gainer', value: topGainer ? topGainer.symbol.toUpperCase() + ' ' + this.$root.changeFormat(topGainer.price_change_percentage_24h_in_currency) : 'N/A'},
                            {label: 'Top loser', value: topLoser ? topLoser.symbol.toUpperCase() + ' ' + this.$root.changeFormat(topLoser.price_change_percentage_24h_in_currency) : 'N/A'}
                        ]
                    };
                }
            },
            methods: {
                fetchPulse: function () {
                    this.fetchGlobal();
                    this.fetchMarketCurrencies();
                    this.fetchTrendingCoins();
                    this.fetchTonCuration();
                },
                fetchTonCuration: function () {
                    if (!axios) return Promise.resolve();

                    this.loadingTonCuration = true;
                    this.tonCurationError = false;

                    return axios.get(tonEndpoint)
                        .then(response => {
                            const payload = response.data && response.data.ok === true ? response.data.data : response.data;
                            this.tonAssets = _.get(payload, 'assets', []);
                        })
                        .catch(() => {
                            this.tonAssets = [];
                            this.tonCurationError = true;
                        })
                        .finally(() => this.loadingTonCuration = false);
                },
                fetchTonMarkets: function () {
                    const ids = this.tonAssetCoinIds;
                    if (!ids.length) {
                        this.tonMarketCurrencies = [];
                        return Promise.resolve([]);
                    }

                    this.loadingTonMarkets = true;
                    this.tonMarketError = false;

                    return CoinGecko.coinsMarkets({
                        ids: ids.join(','),
                        per_page: Math.min(250, ids.length),
                        page: 1,
                        order: 'market_cap_desc',
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: options.priceChanges.join(','),
                        sparkline: false
                    })
                        .then(currencies => {
                            this.tonMarketCurrencies = (currencies || []).map(currency => this.extendCurrency(currency));
                            return this.tonMarketCurrencies;
                        })
                        .catch(() => {
                            this.tonMarketCurrencies = [];
                            this.tonMarketError = true;
                            return [];
                        })
                        .finally(() => this.loadingTonMarkets = false);
                },
                initWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(() => this.syncWatchlistIds());
                    watchlist.init().then(() => this.syncWatchlistIds());
                },
                syncWatchlistIds: function () {
                    this.watchlistIds = GeckoClient.watchlist ? GeckoClient.watchlist.ids() : [];
                },
                fetchGlobal: function () {
                    this.loadingGlobal = true;
                    this.globalError = false;

                    return CoinGecko.global()
                        .then(global => {
                            this.global = global;
                            this.globalMeta = CoinGecko.metaGet('global', undefined) || null;
                        })
                        .catch(() => this.globalError = true)
                        .finally(() => this.loadingGlobal = false);
                },
                fetchMarketCurrencies: function () {
                    const params = {
                        per_page: perPage,
                        page: 1,
                        order: options.order,
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: options.priceChanges.join(','),
                        sparkline: true
                    };

                    this.marketConfig = {params: params};
                    this.loadingMarkets = true;
                    this.marketError = false;

                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            this.marketCurrencies = currencies.map(currency => this.extendCurrency(currency));
                            this.marketMeta = CoinGecko.metaGet('coins/markets', this.marketConfig) || null;
                            this.trackMarketPulseAchievements();
                        })
                        .catch(() => {
                            this.marketError = true;
                            this.marketCurrencies = [];
                        })
                        .finally(() => this.loadingMarkets = false);
                },
                fetchTrendingCoins: function () {
                    this.loadingTrending = true;
                    this.trendingError = false;

                    return CoinGecko.searchTrending()
                        .then(trending => {
                            this.trendingCoins = (trending.coins || [])
                                .slice(0, 6)
                                .map(coin => this.extendCurrency(coin));
                        })
                        .catch(() => this.trendingError = true)
                        .finally(() => this.loadingTrending = false);
                },
                extendCurrency: function (currency) {
                    currency.route = {name: 'currency', params: {id: currency.id}};
                    return currency;
                },
                aiCurrencySnapshot: function (currency) {
                    return GeckoClient.ai ? GeckoClient.ai.marketCurrencySnapshot(currency) : {};
                },
                isWatched: function (currency) {
                    return currency && this.watchlistIds.indexOf(currency.id) >= 0;
                },
                watchlistIcon: function (currency) {
                    return this.isWatched(currency) ? 'mdi-star' : 'mdi-star-outline';
                },
                watchlistLabel: function (currency) {
                    return (this.isWatched(currency) ? 'Remove ' : 'Add ') + currency.name + ' ' + (this.isWatched(currency) ? 'from' : 'to') + ' Watchlist';
                },
                toggleWatchlist: function (currency) {
                    if (!currency || !GeckoClient.watchlist) return;

                    GeckoClient.watchlist.toggle(currency, {sourceRoute: 'market_pulse'})
                        .then(() => this.syncWatchlistIds());
                },
                relativeTime: function (timestamp) {
                    const date = new Date(timestamp);
                    if (!GeckoClient.utils.isValidDate(date)) return '';

                    const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
                    if (seconds < 60) return 'now';
                    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                    return Math.floor(seconds / 86400) + 'd ago';
                },
                focusSearch: function () {
                    const input = document.querySelector('.gc-search-bar input[type="text"]');
                    if (input) {
                        input.focus();
                        input.click();
                    }
                },
                shareMarketPulse: function () {
                    if (!GeckoClient.share) return;
                    GeckoClient.share.share(this.marketPulseShareCard);
                },
                trackMarketPulseAchievements: function () {
                    if (!GeckoClient.achievements) return;

                    GeckoClient.achievements.track('market_check', {source_route: 'market_pulse'});
                    const movers = this.topGainers.concat(this.topLosers);
                    const strongest = _.maxBy(movers, currency => Math.abs(percentChange(currency)));
                    if (strongest) {
                        GeckoClient.achievements.trackMarketMovement(strongest, 'market_pulse');
                    }
                }
            }
        }
    });

})(window, _, axios, CoinGecko, GeckoClient);

(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const currencyRouteConfig = GeckoClient.routesConfig.currency;
    const coinsRouteConfig = GeckoClient.routesConfig.coins;

    const mainOptions = GeckoClient.getOptions('currency');

    const marketOptions = GeckoClient.getOptions('currency-market');

    const historicalOptions = GeckoClient.getOptions('currency-historical');
    // CoinGecko has auto granularity, min 120 day period to force 1-day interval
    const historicalPeriodDays  = Math.max(120, historicalOptions.periodDays) || 120;
    const historicalPeriodSecs  = historicalPeriodDays * 3600 * 24;
    const historicalToTimestamp = parseInt(new Date() / 1000);


    function currencyComponent() {
        return {
            template: '#route-currency',
            data: function () {
                return {
                    currencyId: this.$route.params.id,
                    currency: null,
                    tabsModel: null,
                    tab: null,
                    tabs: mainOptions.tabs,
                    loading: false,
                    actionNotice: '',
                    actionNoticeModel: false,

                    marketLoading: false,
                    marketTableHeaders: marketOptions.tableHeaders,
                    marketTickers: [],
                    marketPage: 0,
                    marketOrder: 'volume_desc',
                    marketPerPage: 100, // must be 100
                    marketLoadMore: true,
                    marketLoadingMore: false,

                    historicalLoading: false,
                    historicalTableHeaders: historicalOptions.tableHeaders,
                    historicalData: [],
                    historicalToTimestamp: historicalToTimestamp,
                    historicalPeriodDays: historicalPeriodDays,
                    historicalPeriodSecs: historicalPeriodSecs,
                    historicalLoadMore: true,
                    historicalLoadMoreLoading: false,

                    watchlistIds: [],
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                this.initWatchlist();
                this.fetchCurrency()
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            beforeRouteUpdate: function (to, from, next) {
                // reset and fetch new currency in currency to currency route transition
                this.resetData();
                this.currencyId = to.params.id;
                this.fetchCurrency()
                    .then(() => next())
                    .then(() => this.tabChanged(this.tabsModel)); // open the same tab
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    this.resetData();
                    // fetch currency with new vs currency values
                    this.fetchCurrency().then(() => this.tabChanged(this.tabsModel)); // open the same tab
                },
                tabsModel: function (index) {
                    this.tabChanged(index)
                }
            },
            computed: {
                isInWatchlist: function () {
                    return this.isWatched(this.currency);
                },
                watchlistButtonLabel: function () {
                    return this.watchlistLabel(this.currency);
                },
                alertButtonLabel: function () {
                    return this.currency ? 'Create alert for ' + this.currency.name : 'Create alert';
                },
                shareButtonLabel: function () {
                    return this.currency ? 'Share ' + this.currency.name : 'Share coin';
                },
                currencyShareCard: function () {
                    if (!this.currency) return null;

                    const symbol = _.toUpper(this.currency.symbol || '');
                    const price = this.currency.currentPrice ? this.$root.priceFormat(this.currency.currentPrice) : 'Price unavailable';
                    const change = _.isFinite(parseFloat(this.currency.change24hPercent))
                        ? this.$root.changeFormat(this.currency.change24hPercent)
                        : '24h unavailable';

                    return {
                        title: this.currency.name + ' price',
                        subtitle: symbol ? symbol + ' market card' : 'Coin market card',
                        body: price + ' with 24h move ' + change + ' on TONBANKCARD.',
                        route: '/currency/' + encodeURIComponent(this.currency.id),
                        campaign: 'coin-price',
                        context: 'coin_price',
                        freshness: this.currencyShareFreshnessLabel(),
                        metrics: [
                            {label: 'Price', value: price},
                            {label: '24h', value: change},
                            {label: 'Market cap', value: this.currency.marketCap ? this.$root.marketCapFormat(this.currency.marketCap) : 'N/A'},
                            {label: 'Rank', value: this.currency.market_cap_rank ? '#' + this.currency.market_cap_rank : 'N/A'}
                        ]
                    };
                },
                coinInsightContext: function () {
                    if (!this.currency || !GeckoClient.ai) return null;

                    return {
                        insight_type: 'coin_summary',
                        subject: this.currency.name + ' (' + _.toUpper(this.currency.symbol) + ')',
                        market_data_age_seconds: this.currencyMarketAgeSeconds(),
                        market_data_updated_at: this.currencyMarketUpdatedAt(),
                        market_data: this.currencyInsightMarketData()
                    };
                },
                alertInsightContext: function () {
                    if (!this.currency || !GeckoClient.ai) return null;

                    return {
                        insight_type: 'alert_explanation',
                        subject: 'Alert context for ' + this.currency.name,
                        market_data_age_seconds: this.currencyMarketAgeSeconds(),
                        market_data_updated_at: this.currencyMarketUpdatedAt(),
                        market_data: Object.assign(
                            this.currencyInsightMarketData(),
                            {
                                watchlisted: this.isWatched(this.currency),
                                alert_context: {
                                    current_price: this.currency.currentPrice,
                                    high_24h: this.currency.high24h,
                                    low_24h: this.currency.low24h,
                                    change_24h_percent: this.currency.change24hPercent,
                                    volume_market_cap_ratio: this.currency.volumePerMarketCap
                                }
                            }
                        )
                    };
                }
            },
            methods: {
                initWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(() => this.syncWatchlistIds());
                    watchlist.init().then(() => this.syncWatchlistIds());
                },
                syncWatchlistIds: function () {
                    this.watchlistIds = GeckoClient.watchlist ? GeckoClient.watchlist.ids() : [];
                },
                isWatched: function (currency) {
                    return currency && this.watchlistIds.indexOf(currency.id) >= 0;
                },
                watchlistIcon: function (currency) {
                    return this.isWatched(currency) ? 'mdi-star' : 'mdi-star-outline';
                },
                watchlistLabel: function (currency) {
                    if (!currency) return 'Watchlist';
                    return (this.isWatched(currency) ? 'Remove ' : 'Add ') + currency.name + ' ' + (this.isWatched(currency) ? 'from' : 'to') + ' Watchlist';
                },
                toggleWatchlist: function (currency) {
                    if (!currency || !GeckoClient.watchlist) return;

                    const wasWatched = this.isWatched(currency);
                    GeckoClient.watchlist.toggle(
                        {
                            id: currency.id,
                            symbol: currency.symbol,
                            name: currency.name,
                            image: _.get(currency, 'image.large') || _.get(currency, 'image.small') || _.get(currency, 'image.thumb')
                        },
                        {sourceRoute: 'coin_detail'}
                    ).then(() => {
                        this.syncWatchlistIds();
                        this.showActionNotice(currency.name + (wasWatched ? ' removed from watchlist.' : ' added to watchlist.'));
                    });
                },
                resetData: function () {
                    this.marketTickers = [];
                    this.marketLoading = false;
                    this.marketPage = 0;
                    this.marketLoadMore = true;
                    this.marketLoadingMore = false;

                    this.historicalData = [];
                    this.historicalLoading = false;
                    this.historicalToTimestamp = historicalToTimestamp;
                    this.historicalLoadMore = true;
                    this.historicalLoadMoreLoading = false;
                },
                fetchCurrency: function () {
                    this.loading = true;

                    const params = {
                        market_data: true,
                        localization: false,
                        tickers: false,
                        sparkline: false
                    };
                    return CoinGecko.coin(this.currencyId, params)
                        .then(currency => {
                            // avoid crossing requests
                            if (currency.id === this.currencyId) {
                                this.currency = this.extendCurrency(currency);
                                // update title meta tags
                                setTitle(currency.name + ' (' + _.toUpper(currency.symbol) + ')');
                            }
                            return currency;
                        })
                        .catch(err => this.$router.push({name: 'currencies'})) // redirect to table if fails
                        .finally(() => this.loading = false);
                },
                extendCurrency: function (currency) {
                    // extend with converted and calculated market data properties
                    const md = currency.market_data = currency.market_data || {};
                    currency.currentPrice = this.vsConverted(md.current_price);
                    currency.change24hPercent = this.vsConverted(md.price_change_percentage_24h_in_currency);
                    currency.high24h = this.vsConverted(md.high_24h);
                    currency.low24h = this.vsConverted(md.low_24h);
                    currency.marketCap = this.vsConverted(md.market_cap);
                    currency.marketCapChange24h = this.vsConverted(md.market_cap_change_24h_in_currency);
                    currency.marketCapChange24hPercent = this.vsConverted(md.market_cap_change_percentage_24h_in_currency);
                    currency.fullyDilutedValuation = this.vsConverted(md.fully_diluted_valuation);
                    currency.totalVolume = this.vsConverted(md.total_volume);
                    currency.circulatingSupply = md.circulating_supply || null;
                    currency.totalSupply = md.total_supply || null;
                    currency.isTonAsset = this.isTonAsset(currency);

                    const marketCap = parseFloat(currency.marketCap);
                    const totalVolume = parseFloat(currency.totalVolume);
                    const volumePerMarketCap = totalVolume / marketCap;
                    currency.volumePerMarketCap = _.isFinite(volumePerMarketCap) ? volumePerMarketCap : null;

                    return currency;
                },
                vsConverted: function (priceObj) {
                    return _.get(priceObj, this.$root.vsCurrencyId, null);
                },
                currencyMarketUpdatedAt: function () {
                    return _.get(this.currency, 'last_updated') || _.get(this.currency, 'market_data.last_updated') || null;
                },
                currencyMarketAgeSeconds: function () {
                    return GeckoClient.ai ? GeckoClient.ai.marketDataAgeSeconds({last_updated_at: this.currencyMarketUpdatedAt()}) : 0;
                },
                currencyShareFreshnessLabel: function () {
                    const timestamp = this.currencyMarketUpdatedAt();
                    return timestamp ? 'Updated ' + this.relativeTime(timestamp) : 'Freshness unavailable';
                },
                currencyInsightMarketData: function () {
                    const currency = this.currency || {};
                    return {
                        vs_currency: this.$root.vsCurrencyId,
                        asset: {
                            id: currency.id,
                            symbol: currency.symbol,
                            name: currency.name,
                            rank: currency.market_cap_rank,
                            price: currency.currentPrice,
                            change_24h_percent: currency.change24hPercent,
                            market_cap: currency.marketCap,
                            market_cap_change_24h: currency.marketCapChange24h,
                            market_cap_change_24h_percent: currency.marketCapChange24hPercent,
                            volume_24h: currency.totalVolume,
                            circulating_supply: currency.circulatingSupply,
                            total_supply: currency.totalSupply,
                            volume_market_cap_ratio: currency.volumePerMarketCap,
                            is_ton_asset: currency.isTonAsset
                        },
                        community_score: _.get(currency, 'community_score', null),
                        developer_score: _.get(currency, 'developer_score', null),
                        liquidity_score: _.get(currency, 'liquidity_score', null),
                        coingecko_score: _.get(currency, 'coingecko_score', null)
                    };
                },
                prepareAlertDraft: function () {
                    if (!this.currency) return;

                    const draft = {
                        coin_id: this.currency.id,
                        symbol: this.currency.symbol,
                        name: this.currency.name,
                        vs_currency: this.$root.vsCurrencyId,
                        created_at: (new Date()).toISOString()
                    };

                    const alertDraftKey = _.get(GeckoClient, 'alertsConfig.draftStorageKey', 'TONBANKCARD:alertDraft');
                    window.localStorage.setItem(alertDraftKey, JSON.stringify(draft));
                    this.showActionNotice('Opening alert draft for ' + this.currency.name + '.');
                    this.$router.push({
                        name: 'alerts',
                        query: {
                            coin: this.currency.id,
                            symbol: this.currency.symbol
                        }
                    }).catch(() => {});
                },
                shareCurrency: function () {
                    if (!this.currency || !GeckoClient.share) return;

                    GeckoClient.share.share(this.currencyShareCard)
                        .then(shared => {
                            if (shared) this.showActionNotice('Share link ready for ' + this.currency.name + '.');
                        });
                },
                showActionNotice: function (message) {
                    this.actionNotice = message;
                    this.actionNoticeModel = true;
                },
                isTonAsset: function (currency) {
                    const id = _.toLower(_.get(currency, 'id', ''));
                    const symbol = _.toLower(_.get(currency, 'symbol', ''));
                    const platforms = _.keys(_.get(currency, 'platforms', {})).map(key => _.toLower(key));
                    const categories = (_.get(currency, 'categories', []) || []).map(category => _.toLower(category));

                    return id === 'toncoin'
                        || id === 'the-open-network'
                        || symbol === 'ton'
                        || platforms.indexOf('the-open-network') >= 0
                        || platforms.indexOf('ton') >= 0
                        || categories.some(category => category.indexOf('ton ecosystem') >= 0 || category.indexOf('the open network') >= 0);
                },
                relativeTime: function (timestamp) {
                    const date = new Date(timestamp);
                    if (!GeckoClient.utils.isValidDate(date)) return 'unknown';

                    const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
                    if (seconds < 60) return 'now';
                    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                    return Math.floor(seconds / 86400) + 'd ago';
                },
                tabChanged: function (index) {
                    this.tab = this.tabs[index];
                    switch (this.tab) {
                        case 'market': return this.showMarket();
                        case 'historical': return this.showHistoricalData();
                    }
                },
                fetchTickers: function () {
                    const params = {
                        include_exchange_logo: true,
                        per_page: this.marketPerPage,
                        page: ++this.marketPage,
                        order: this.marketOrder
                    }
                    return CoinGecko.coinTickers(this.currencyId, params)
                        .then(tickers => {
                            tickers.forEach(ticker => this.marketTickers.push(this.extendTicker(ticker)));
                            this.marketLoadMore = tickers.length === this.marketPerPage;
                            return tickers;
                        })
                        .catch(err => this.marketLoadMore = false);
                },
                showMarket: function () {
                    // if has tickers, do nothing
                    if (this.marketTickers.length || this.marketLoading) return;
                    // fetch first tickers
                    this.marketLoading = true;
                    this.fetchTickers().finally(() => this.marketLoading = false);
                },
                fetchMoreTickers: function () {
                    this.marketLoadingMore = true;
                    return this.fetchTickers().finally(() => this.marketLoadingMore = false);
                },
                extendTicker: function (ticker) {
                    // extend with properties for table usage
                    const $root = this.$root;

                    ticker.pair = ticker.base + '/' + ticker.target;
                    ticker.pairDisplay = $root.pairDisplay(ticker.base, ticker.target);

                    ticker.exchangeName = ticker.market.name;
                    ticker.exchangeLogo = ticker.market.logo;
                    ticker.exchangeRoute = {
                        name: 'exchange',
                        params: {id: ticker.market.identifier}
                    };

                    // avoid addresses as symbols
                    const target = _.toLower(ticker.target).indexOf('0x') === 0 ? false : ticker.target;
                    const base   = _.toLower(ticker.base).indexOf('0x') === 0 ? false : ticker.base;

                    ticker.converted_last = ticker.converted_last || {};
                    ticker.lastUSD = parseFloat(ticker.converted_last.usd) || 0;
                    ticker.lastFormatted = $root.priceTargetFormat(ticker.last, target);
                    ticker.volumeFormatted = $root.volumeTargetFormat(ticker.volume, base);
                    ticker.spreadFormatted = $root.spreadFormat(ticker.bid_ask_spread_percentage);

                    // trust details
                    ticker.trustColor = $root.coinGeckoTrustScoreColor(ticker.trust_score);
                    ticker.trustTextColor = $root.coinGeckoTrustScoreTextColor(ticker.trust_score);
                    ticker.trustScore = $root.coinGeckoTrustScoreInteger(ticker.trust_score);
                    ticker.trustText = $root.coinGeckoTrustScoreText(ticker.trust_score);

                    return ticker;
                },
                fetchHistoricalData: function () {
                    // calculate "from" subtracting a full period to current upper limit
                    const from = this.historicalToTimestamp - this.historicalPeriodSecs;
                    const params = {
                        vs_currency: this.$root.vsCurrencyId,
                        from: from,
                        to: this.historicalToTimestamp
                    };
                    return CoinGecko.coinMarketChartRange(this.currencyId, params)
                        .then(data => {
                            this.historicalLoadMore = data.prices.length === this.historicalPeriodDays;
                            // need to be added in reverse order
                            return _.eachRight(data.prices, (p, index) => {
                                this.historicalData.push({
                                    timestamp: data.prices[index][0],
                                    price: data.prices[index][1],
                                    marketCap: data.market_caps[index][1],
                                    volume: data.total_volumes[index][1]
                                });
                            });
                        })
                        .catch(err => this.historicalLoadMore = false)
                        .finally(() => this.historicalToTimestamp = from - 1);
                },
                fetchMoreHistoricalData: function () {
                    this.historicalLoadMoreLoading = true;
                    return this.fetchHistoricalData().finally(() => this.historicalLoadMoreLoading = false);
                },
                showHistoricalData: function () {
                    // if has data, do nothing
                    if (this.historicalData.length || this.historicalLoading) return;
                    // fetch first entries
                    this.historicalLoading = true;
                    this.fetchHistoricalData().finally(() => this.historicalLoading = false);
                }
            }
        };
    }

    GeckoClient.router.addRoute({
        name: 'currency',
        path: currencyRouteConfig.path,
        component: currencyComponent()
    });

    if (coinsRouteConfig) {
        GeckoClient.router.addRoute({
            name: 'coins',
            path: coinsRouteConfig.path,
            component: currencyComponent()
        });
    }

})(window, _, CoinGecko, GeckoClient);

(function (window, document, _, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig['crypto-exchange'];
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;
    const options = GeckoClient.getOptions('crypto-exchange', {});
    const defaultFrom = sanitizeCurrency(options.defaultFrom, 'ton');
    const defaultTo = sanitizeCurrency(options.defaultTo, 'usdtton');
    const widgetBaseUrl = options.widgetBaseUrl || 'https://changenow.io/embeds/exchange-widget/v2/widget.html';
    const stepperScriptUrl = options.stepperScriptUrl || 'https://changenow.io/embeds/exchange-widget/v2/stepper-connector.js';
    const fallbackLinkId = '3cc0024a18fd9d';

    function sanitizeCurrency(value, fallback) {
        const sanitized = _.toLower(_.trim(value || '')).replace(/[^a-z0-9]/g, '');
        return sanitized || fallback || '';
    }

    function sanitizeAssetId(value) {
        return _.toLower(_.trim(value || '')).replace(/[^a-z0-9-]/g, '');
    }

    function sameQuery(left, right) {
        return ['from', 'to', 'asset'].every(key => (left[key] || '') === (right[key] || ''));
    }

    GeckoClient.router.addRoute({
        name: 'crypto-exchange',
        path: routeConfig.path,
        component: {
            template: '#route-crypto-exchange',
            data: function () {
                return {
                    fromInput: defaultFrom,
                    toInput: defaultTo,
                    assetId: '',
                    presets: options.presets || []
                };
            },
            computed: {
                normalizedFrom: function () {
                    return sanitizeCurrency(this.fromInput, defaultFrom);
                },
                normalizedTo: function () {
                    return sanitizeCurrency(this.toInput, defaultTo);
                },
                pairLabel: function () {
                    return this.assetLabel(this.normalizedFrom) + ' to ' + this.assetLabel(this.normalizedTo);
                },
                widgetSrc: function () {
                    return this.buildWidgetSrc(this.normalizedFrom, this.normalizedTo);
                }
            },
            created: function () {
                setTitle(options.title || 'Crypto Exchange');
                this.applyRouteQuery(this.$route.query || {});
            },
            mounted: function () {
                this.loadStepperConnector();
            },
            beforeRouteUpdate: function (to, from, next) {
                this.applyRouteQuery(to.query || {});
                next();
            },
            methods: {
                applyRouteQuery: function (query) {
                    this.fromInput = sanitizeCurrency(query.from, defaultFrom);
                    this.toInput = sanitizeCurrency(query.to, defaultTo);
                    this.assetId = sanitizeAssetId(query.asset || '');
                },
                routeQuery: function () {
                    const query = {
                        from: this.normalizedFrom,
                        to: this.normalizedTo
                    };

                    if (this.assetId) {
                        query.asset = this.assetId;
                    }

                    return query;
                },
                syncRoute: function () {
                    const query = this.routeQuery();
                    if (sameQuery(this.$route.query || {}, query)) return;

                    this.$router.replace({name: 'crypto-exchange', query}).catch(() => {});
                },
                swapPair: function () {
                    const from = this.normalizedFrom;
                    const to = this.normalizedTo;

                    this.fromInput = to;
                    this.toInput = from;
                    this.assetId = '';
                    this.syncRoute();
                },
                applyPreset: function (preset) {
                    this.fromInput = sanitizeCurrency(preset.from, defaultFrom);
                    this.toInput = sanitizeCurrency(preset.to, defaultTo);
                    this.assetId = sanitizeAssetId(preset.asset || '');
                    this.syncRoute();
                },
                assetLabel: function (value) {
                    const id = sanitizeCurrency(value, '');
                    return _.get(options.assetLabels, id) || _.toUpper(id);
                },
                buildWidgetSrc: function (from, to) {
                    const url = new URL(widgetBaseUrl);
                    const params = {
                        FAQ: 'true',
                        amount: '1',
                        backgroundColor: 'f6fafd',
                        darkMode: this.$root && this.$root.darkTheme ? 'true' : 'false',
                        from: from,
                        horizontal: 'false',
                        isFiat: 'false',
                        lang: 'en-EN',
                        link_id: options.linkId || fallbackLinkId,
                        locales: 'true',
                        logo: 'false',
                        primaryColor: '1bb2da',
                        to: to,
                        toTheMoon: 'false'
                    };

                    url.search = '';
                    Object.keys(params).forEach(key => url.searchParams.set(key, params[key]));
                    url.searchParams.append('amountFiat', '');

                    return url.toString();
                },
                loadStepperConnector: function () {
                    if (document.querySelector('script[data-changenow-stepper="true"]')) return;

                    const script = document.createElement('script');
                    script.defer = true;
                    script.type = 'text/javascript';
                    script.src = stepperScriptUrl;
                    script.setAttribute('data-changenow-stepper', 'true');
                    document.body.appendChild(script);
                }
            }
        }
    });

})(window, document, _, GeckoClient);

(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig.derivatives;
    if (!routeConfig) return;

    const __ = GeckoClient.__;
    const setTitle = GeckoClient.setTitle;

    const derivativesOptions = GeckoClient.getOptions('derivatives');
    const tableHeaders = derivativesOptions.tableHeaders.filter(header => header.show);


    GeckoClient.router.addRoute({
        name: 'derivatives',
        path: routeConfig.path,
        component: {
            template: '#route-derivatives',
            data: function () {
                return {
                    tableFooterProps: derivativesOptions.tableFooterProps,
                    derivatives: [],
                    markets: [],
                    loading: false,
                    search: null,
                    selectedType: derivativesOptions.defaultType,
                    types: derivativesOptions.types,
                    selectedMarket: 'all',
                };
            },
            computed: {
                tableHeaders: function () {
                    // filter specific type headers
                    return tableHeaders.filter(header => !header.type || header.type === this.selectedType);
                },
                isFiltered: function () {
                    return this.search || this.selectedMarket !=='all' || this.selectedType !== 'all';
                },
                items: function () {
                    let items = this.derivatives;
                    // filter if specific type is selected
                    if (this.selectedType !== 'all') items = items.filter(d => d.contract_type === this.selectedType);
                    // filter if specific market is selected
                    if (this.selectedMarket !== 'all') items = items.filter(d => d.market === this.selectedMarket);
                    return items;
                }
            },
            created: function () {
                this.fetchDerivatives();
                // update title meta tags
                setTitle(derivativesOptions.title);
            },
            methods: {
                fetchDerivatives() {
                    this.loading = true;
                    return CoinGecko.derivatives()
                        .then(derivatives => {
                            this.markets = [{value: 'all', text: __('All')}];

                            // sort markets for easier look up on the select element
                            const marketsMap = new Map();
                            derivatives.forEach(derivative => {
                                this.derivatives.push(derivative);
                                if (derivative.market) marketsMap.set(derivative.market.toLowerCase(), derivative.market);
                            });
                            Array.from(marketsMap.entries())
                                .sort()
                                .forEach(entry => this.markets.push({value: entry[1], text: entry[1]}));

                            return derivatives;
                        })
                        .finally(() => this.loading = false);
                },
                clearFilters: function () {
                    // sets filters defaults
                    this.search = null;
                    this.selectedMarket = 'all';
                    this.selectedType = 'all';
                },
                getMarketUrl: function (market) {
                    return GeckoClient.getCustomLink('derivativesMarkets', market);
                }
            }
        },
    });

})(window, CoinGecko, GeckoClient);
(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const exchangeOptions = GeckoClient.getOptions('exchange');

    GeckoClient.router.addRoute({
        name: 'exchange',
        path: GeckoClient.routesConfig.exchange.path,
        component: {
            template: '#route-exchange',
            data: function () {
                return {
                    exchangeId: this.$route.params.id,
                    exchange: null,
                    tableHeaders: exchangeOptions.tableHeaders,
                    loading: false,
                    tickers: [],
                    tickersPage: 1,
                    tickersPerPage: 100, // must be 100
                    tickersLoadMore: true,
                    tickersLoading: false
                };
            },
            created: function () {
                this.fetchExchange();
            },
            beforeRouteUpdate: function (to, from, next) {
                // reset and fetch new exchange in exchange to exchange route transition
                this.resetData();
                this.exchangeId = to.params.id;
                this.fetchExchange().then(() => next());
            },
            methods: {
                resetData: function () {
                    this.loading = false;
                    this.tickers = [];
                    this.tickersPage = 1;
                    this.tickersLoadMore = true;
                    this.tickersLoading = false;
                },
                fetchExchange: function () {
                    this.loading = true;
                    return CoinGecko.exchange(this.exchangeId, null)
                        .then(exchange => {
                            this.exchange = exchange;
                            this.tickers = _.each(exchange.tickers, ticker => this.extendTicker(ticker));
                            this.tickersLoadMore = exchange.tickers.length === this.tickersPerPage;

                            // update title meta tags
                            setTitle(exchange.name);

                            return exchange;
                        })
                        .catch(err => this.$router.push({name: 'exchanges'})) // redirect to table if fails
                        .finally(() => this.loading = false);
                },
                extendTicker: function (ticker) {
                    // extend with properties for table usage

                    ticker.pair = ticker.base + '/' + ticker.target;
                    ticker.pairDisplay = this.$root.pairDisplay(ticker.base, ticker.target);

                    // avoid addresses as symbols
                    const target = _.toLower(ticker.target).indexOf('0x') === 0 ? false : ticker.target;
                    const base   = _.toLower(ticker.base).indexOf('0x') === 0 ? false : ticker.base;

                    ticker.lastUSD = parseFloat(ticker.converted_last.usd) || 0;
                    ticker.lastFormatted = this.$root.priceTargetFormat(ticker.last, target);
                    ticker.volumeUSD = parseFloat(ticker.converted_volume.usd) || 0;
                    ticker.volumeFormatted = this.$root.volumeTargetFormat(ticker.volume, base);
                    ticker.spreadFormatted = this.$root.spreadFormat(ticker.bid_ask_spread_percentage);

                    // trust details
                    ticker.trustColor = this.$root.coinGeckoTrustScoreColor(ticker.trust_score);
                    ticker.trustTextColor = this.$root.coinGeckoTrustScoreTextColor(ticker.trust_score);
                    ticker.trustScore = this.$root.coinGeckoTrustScoreInteger(ticker.trust_score);
                    ticker.trustText = this.$root.coinGeckoTrustScoreText(ticker.trust_score);

                    return ticker;
                },
                fetchTickers: function () {
                    const params = {
                        page: ++this.tickersPage,
                        per_page: this.tickersPerPage
                    };
                    return CoinGecko.exchangeTickers(this.exchangeId, params)
                        .then(tickers => {
                            tickers.forEach(ticker => this.tickers.push(this.extendTicker(ticker)));
                            this.tickersLoadMore = tickers.length === this.tickersPerPage;
                            return tickers;
                        });
                },
                fetchMoreTickers: function () {
                    this.tickersLoading = true;
                    return this.fetchTickers().finally(() => this.tickersLoading = false);
                },
            }
        }
    });

})(window, CoinGecko, GeckoClient);

(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const exchangesOptions = GeckoClient.getOptions('exchanges');
    const tableHeaders = exchangesOptions.tableHeaders.filter(header => header.show);
    const perPage = Math.min(250, exchangesOptions.perPage) || 100;

    GeckoClient.router.addRoute({
        name: 'exchanges',
        path: GeckoClient.routesConfig.exchanges.path,
        component: {
            template: '#route-exchanges',
            data: function () {
                return {
                    tableHeaders: tableHeaders,
                    exchanges: [],
                    page: 0,
                    perPage: perPage,
                    loading: false,
                    loadMore: true,
                    loadMoreLoading: false
                };
            },
            created: function () {
                this.loading = true;
                this.fetchExchanges().finally(() => this.loading = false);
                // update title meta tags
                setTitle(exchangesOptions.title);
            },
            methods: {
                fetchExchanges: function () {
                    const params = {
                        per_page: this.perPage,
                        page: ++this.page
                    };
                    return CoinGecko.exchanges(params)
                        .then(exchanges => {
                            exchanges.forEach(exchange => this.exchanges.push(this.extendExchange(exchange)));
                            this.loadMore = exchanges.length === this.perPage;
                            return exchanges;
                        })
                        .catch(err => this.loadMore = false);
                },
                fetchMoreExchanges: function () {
                    this.loadMoreLoading = true;
                    return this.fetchExchanges().finally(() => this.loadMoreLoading = false);
                },
                extendExchange: function (exchange) {
                    // extend with properties for table usage
                    const $root = this.$root;

                    // trust details
                    const score = exchange.trust_score;
                    exchange.trustScore = $root.coinGeckoTrustScoreInteger(score);
                    exchange.trustColor = $root.coinGeckoTrustScoreColor(score);
                    exchange.trustTextColor = $root.coinGeckoTrustScoreTextColor(score);
                    exchange.trustText = $root.coinGeckoTrustScoreText(score);

                    exchange.volume24hFormatted = $root.volumeBTCFormat(exchange.trade_volume_24h_btc);
                    exchange.volume24hNormalizedFormatted = $root.volumeBTCFormat(exchange.trade_volume_24h_btc_normalized);

                    exchange.route = {name: 'exchange', params: {id: exchange.id}}

                    return exchange;
                },
                toExchange: function (exchange) {
                    this.$router.push(exchange.route);
                }
            }
        }
    });

})(window, CoinGecko, GeckoClient);

(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig['finance-platforms'];
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;

    const financePlatformsOptions = GeckoClient.getOptions('finance-platforms');

    GeckoClient.router.addRoute({
        name: 'finance-platforms',
        path: routeConfig.path,
        component: {
            template: '#route-finance-platforms',
            data: function () {
                return {
                    types: financePlatformsOptions.types,
                    selectedType: 'all',
                    platforms: [],
                    loading: false,
                    perPage: 250,
                };
            },
            created: function () {
                this.loading = true;
                this.fetchPlatforms().finally(() => this.loading = false);
                // update title meta tags
                setTitle(financePlatformsOptions.title);
            },
            computed: {
                filteredPlatforms: function () {
                    // restricted
                    if (this.selectedType !== 'all') {
                        const centralized = this.selectedType === 'cefi';
                        return _.filter(this.platforms, ['centralized', centralized]);
                    }
                    return this.platforms; // all
                }
            },
            methods: {
                fetchPlatforms: function () {
                    return CoinGecko.financePlatforms({per_page: this.perPage})
                        .then(platforms => {
                            return this.platforms = financePlatformsOptions.sort ? this.sortPlatforms(platforms) : platforms;
                        });
                },
                sortPlatforms: function (platforms) {
                    return _.sortBy(platforms, platform => _.deburr(_.toLower(platform.name)))
                }
            }
        }
    });

})(window, _, CoinGecko, GeckoClient);

(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig['finance-products'];
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;

    const financeProductsOptions = GeckoClient.getOptions('finance-products');

    GeckoClient.router.addRoute({
        name: 'finance-products',
        path: routeConfig.path,
        component: {
            template: '#route-finance-products',
            data: function () {
                return {
                    tableHeaders: financeProductsOptions.tableHeaders.filter(header => header.show),
                    products: [],
                    page: 0,
                    perPage: financeProductsOptions.perPage,
                    loading: false,
                    loadMore: true,
                    loadMoreLoading: false,
                    platformsMap: new Map()
                };
            },
            created: function () {
                this.loading = true;
                Promise.all([this.fetchPlatforms(), this.fetchProducts()]).finally(() => this.loading = false);
                // update title meta tags
                setTitle(financeProductsOptions.title);
            },
            methods: {
                fetchPlatforms: function () {
                    return CoinGecko.financePlatforms({per_page: 250})
                        .then(platforms => {
                            platforms.forEach(platform => this.platformsMap.set(platform.name, platform));
                            return platforms;
                        });
                },
                fetchProducts: function () {
                    const params = {
                        per_page: this.perPage,
                        page: ++this.page
                    }
                    return CoinGecko.financeProducts(params)
                        .then(products => {
                            products.forEach(product => this.products.push(product));
                            this.loadMore = products.length === this.perPage;
                            return products;
                        })
                },
                fetchMoreProducts: function () {
                    this.loadMoreLoading = true;
                    return this.fetchProducts().finally(() => this.loadMoreLoading = false);
                },
                platformUrl: function (name) {
                    const platform = this.platformsMap.get(name) || {};
                    return platform.url;
                },
                platformColor: function (name) {
                    const platform = this.platformsMap.get(name);
                    if (platform) return platform.color;
                    return 'grey';
                }
            }
        }
    });

})(window, _, CoinGecko, GeckoClient);

(function (window, _, axios, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const marketsRoute = GeckoClient.routesConfig.markets;
    const marketsOptions = GeckoClient.getOptions('markets');
    const tableHeaders = marketsOptions.tableHeaders.filter(header => header.show);
    const perPage = Math.min(250, marketsOptions.perPage) || 100;
    const percentChange = currency => parseFloat(currency.price_change_percentage_24h_in_currency);

    const normalizeTag = value => {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    };

    if (!marketsRoute) return;

    GeckoClient.router.addRoute({
        name: 'markets',
        path: marketsRoute.path,
        component: {
            template: '#route-markets',
            data: function () {
                return {
                    order: marketsOptions.order,
                    priceChanges: marketsOptions.priceChanges,
                    currencies: [],
                    page: 0,
                    perPage: perPage,
                    loading: false,
                    loadMore: true,
                    loadMoreLoading: false,
                    watchlistIds: [],
                    watchlistUnsubscribe: null,
                    tonAssets: [],
                    tonCategories: {},
                    tonLists: {},
                    loadingTonFilters: false,
                    tonFilterError: ''
                };
            },
            created: function () {
                this.initWatchlist();
                this.fetchTonCuration().finally(() => this.fetchFirstCurrencies());
                // update title meta tags
                setTitle(marketsOptions.title);
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    // refresh values with new vs currency
                    this.fetchFirstCurrencies();
                },
                '$route.query.tag': function () {
                    this.fetchFirstCurrencies();
                }
            },
            computed: {
                activeTonTag: function () {
                    return normalizeTag(_.get(this.$route, 'query.tag', ''));
                },
                tableHeaders: function () {
                    if (this.$vuetify.breakpoint.xs) {
                        // hide rank column in smartphones
                        return _.reject(tableHeaders, ['value', 'market_cap_rank']);
                    }
                    return tableHeaders;
                },
                tonFilterChips: function () {
                    const configured = marketsOptions.tonFilters || [];
                    if (configured.length) {
                        return configured.map(filter => Object.assign({}, filter, {tag: normalizeTag(filter.tag)}));
                    }

                    return _.sortBy(_.map(this.tonCategories, category => ({
                        tag: category.tag || category.id,
                        label: category.title,
                        icon: category.icon || 'mdi-tag-outline'
                    })), 'label');
                },
                activeTonAssets: function () {
                    if (!this.activeTonTag) return [];
                    return this.tonAssets.filter(asset => this.tonAssetMatchesTag(asset, this.activeTonTag));
                },
                activeTonCoinIds: function () {
                    return _.uniq(this.activeTonAssets.map(asset => asset.coin_id).filter(Boolean));
                },
                activeTonFilterLabel: function () {
                    if (!this.activeTonTag) return '';
                    const chip = _.find(this.tonFilterChips, ['tag', this.activeTonTag]);
                    return chip ? chip.label : _.startCase(this.activeTonTag);
                }
            },
            methods: {
                initWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(() => this.syncWatchlistIds());
                    watchlist.init().then(() => this.syncWatchlistIds());
                },
                syncWatchlistIds: function () {
                    this.watchlistIds = GeckoClient.watchlist ? GeckoClient.watchlist.ids() : [];
                },
                tonEndpoint: function () {
                    return marketsOptions.tonApiBaseUrl || '/api/ton/assets';
                },
                fetchTonCuration: function () {
                    if (!axios) return Promise.resolve();

                    this.loadingTonFilters = true;
                    this.tonFilterError = '';

                    return axios.get(this.tonEndpoint())
                        .then(response => {
                            const payload = response.data && response.data.ok === true ? response.data.data : response.data;
                            this.tonAssets = _.get(payload, 'assets', []);
                            this.tonCategories = _.get(payload, 'categories', {});
                            this.tonLists = _.get(payload, 'lists', {});
                        })
                        .catch(() => {
                            this.tonAssets = [];
                            this.tonCategories = {};
                            this.tonLists = {};
                            this.tonFilterError = 'TON filters unavailable';
                        })
                        .finally(() => this.loadingTonFilters = false);
                },
                fetchCurrencies: function () {
                    if (this.activeTonTag && !this.activeTonCoinIds.length) {
                        this.loadMore = false;
                        return Promise.resolve([]);
                    }

                    const params = {
                        per_page: this.activeTonTag ? Math.min(250, this.activeTonCoinIds.length) : this.perPage,
                        page: ++this.page,
                        order: this.order,
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: this.priceChanges.join(','),
                        sparkline: true
                    };

                    if (this.activeTonTag) {
                        params.ids = this.activeTonCoinIds.join(',');
                        params.page = 1;
                    }

                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            _.each(currencies, currency => {
                                currency.route = {name: 'currency', params: {id: currency.id}};
                                currency.tonAsset = this.tonAssetForCurrency(currency);
                                this.currencies.push(currency);
                            })
                            this.loadMore = this.activeTonTag ? false : currencies.length === this.perPage;
                            return currencies;
                        })
                        .catch(err => this.loadMore = false);
                },
                fetchFirstCurrencies: function () {
                    // reset
                    this.currencies = [];
                    this.page = 0;
                    this.loadMore = true;
                    this.loadMoreLoading = false;

                    this.loading = true;
                    return this.fetchCurrencies()
                        .then(currencies => {
                            this.trackMarketAchievements(currencies);
                            return currencies;
                        })
                        .finally(() => this.loading = false);
                },
                fetchMoreCurrencies: function () {
                    this.loadMoreLoading = true;
                    return this.fetchCurrencies().finally(() => this.loadMoreLoading = false);
                },
                trackMarketAchievements: function (currencies) {
                    if (!GeckoClient.achievements || !currencies || !currencies.length) return;

                    GeckoClient.achievements.track('market_check', {source_route: 'markets'});
                    const strongest = _.maxBy(currencies.filter(currency => _.isFinite(percentChange(currency))), currency => Math.abs(percentChange(currency)));
                    if (strongest) {
                        GeckoClient.achievements.trackMarketMovement(strongest, 'markets');
                    }
                },
                tonAssetMatchesTag: function (asset, tag) {
                    tag = normalizeTag(tag);
                    const tags = (asset.tags || []).map(normalizeTag);
                    const lists = (asset.list_ids || []).map(normalizeTag);
                    return _.includes(tags, tag)
                        || _.includes(lists, tag)
                        || normalizeTag(asset.category) === tag
                        || normalizeTag(asset.verification_state) === tag;
                },
                tonAssetForCurrency: function (currency) {
                    const activeIds = this.activeTonTag ? this.activeTonAssets : this.tonAssets;
                    return _.find(activeIds, asset => asset.coin_id === currency.id) || null;
                },
                tonFilterRoute: function (tag) {
                    tag = normalizeTag(tag);
                    return {name: 'markets', query: tag ? {tag: tag} : {}};
                },
                clearTonFilterRoute: function () {
                    return {name: 'markets'};
                },
                tonStateColor: function (asset) {
                    if (!asset) return 'primary';
                    if (asset.verification_state === 'verified') return 'success';
                    if (asset.verification_state === 'curated') return 'primary';
                    return 'warning';
                },
                tonStateLabel: function (asset) {
                    return _.startCase(asset && asset.verification_state ? asset.verification_state : 'TON');
                },
                isWatched: function (currency) {
                    return this.watchlistIds.indexOf(currency.id) >= 0;
                },
                watchlistIcon: function (currency) {
                    return this.isWatched(currency) ? 'mdi-star' : 'mdi-star-outline';
                },
                watchlistLabel: function (currency) {
                    return (this.isWatched(currency) ? 'Remove ' : 'Add ') + currency.name + ' ' + (this.isWatched(currency) ? 'from' : 'to') + ' Watchlist';
                },
                toggleWatchlist: function (currency) {
                    if (!GeckoClient.watchlist) return;

                    GeckoClient.watchlist.toggle(currency, {sourceRoute: 'markets'})
                        .then(() => this.syncWatchlistIds());
                },
                toCurrency: function (currency) {
                    this.$router.push(currency.route);
                }
            }
        }
    });

})(window, _, axios, CoinGecko, GeckoClient);

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

(function (window, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    ['support'].forEach(routeName => {
        const routeConfig = GeckoClient.routesConfig[routeName];
        if (!routeConfig) return;

        const routeOptions = GeckoClient.getOptions(routeName, {});

        GeckoClient.router.addRoute({
            name: routeName,
            path: routeConfig.path,
            component: {
                template: '#route-' + routeName,
                created: function () {
                    setTitle(routeOptions.title);
                }
            }
        });
    });

})(window, GeckoClient);

(function (window, _, axios, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.admin;
    if (!route) return;

    const options = GeckoClient.getOptions('admin', {title: 'Admin Panel', apiBaseUrl: '/api/admin'});
    const tokenStorageKey = 'TONBANKCARD:adminToken';
    const adminRouteNames = [
        'admin',
        'admin-providers',
        'admin-feature-flags',
        'admin-ton-assets',
        'admin-legal-copy',
        'admin-alerts',
        'admin-cache',
        'admin-audit-log'
    ];

    function clone(value) {
        return JSON.parse(JSON.stringify(value || {}));
    }

    function defaultProviders() {
        return {
            coingecko: {api_plan: 'demo', api_key: {configured: false}},
            groq: {model_id: 'llama-3.3-70b-versatile', api_key: {configured: false}},
            upstash: {status: 'not_configured', rest_url_configured: false, rest_token: {configured: false}},
            telegram: {bot_username: '', bot_token: {configured: false}},
            changenow: {link_id: '', listing_url: ''}
        };
    }

    function defaultContent() {
        return {
            legal_copy: {
                market_data_disclaimer: '',
                ai_disclaimer: '',
                alerts_disclaimer: '',
                widget_disclaimer: ''
            },
            ton_assets: []
        };
    }

    function defaultOperations() {
        return {
            cache: {
                market_stale_mode: 'serve_stale',
                purge_status: 'idle',
                last_purge_scope: null,
                last_purge_at: null
            },
            alert_thresholds: {
                max_alerts_per_user: 20,
                default_frequency_cap_seconds: 3600,
                default_max_deliveries_per_day: 8,
                evaluation_interval_seconds: 300
            },
            achievements: {
                enabled: false,
                daily_streak_points: 10,
                share_points: 5,
                alert_points: 10
            }
        };
    }

    function component() {
        return {
            template: '#route-admin',
            data: function () {
                return {
                    token: localStorage.getItem(tokenStorageKey) || '',
                    loginToken: '',
                    actor: {},
                    meta: {},
                    featureFlags: {},
                    providers: defaultProviders(),
                    providerSecrets: {
                        coingecko: {api_key: ''},
                        groq: {api_key: ''},
                        upstash: {rest_token: ''},
                        telegram: {bot_token: ''}
                    },
                    content: defaultContent(),
                    operations: defaultOperations(),
                    auditLog: [],
                    loading: false,
                    saving: false,
                    notice: '',
                    noticeType: 'info',
                    cacheScope: 'all'
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title || 'Admin Panel');
                if (this.token) this.loadConfig();
            },
            computed: {
                authenticated: function () {
                    return !!this.actor.role && !!this.token;
                },
                canWrite: function () {
                    return _.get(this.actor, 'permissions.write') === true;
                },
                activeSection: function () {
                    const name = _.get(this.$route, 'name') || 'admin';
                    const map = {
                        'admin': 'overview',
                        'admin-providers': 'providers',
                        'admin-feature-flags': 'flags',
                        'admin-ton-assets': 'ton-assets',
                        'admin-legal-copy': 'legal-copy',
                        'admin-alerts': 'operations',
                        'admin-cache': 'overview',
                        'admin-audit-log': 'audit-log'
                    };

                    return map[name] || 'overview';
                },
                storeLabel: function () {
                    if (_.get(this.meta, 'store_configured')) return _.get(this.meta, 'store_loaded') ? 'store loaded' : 'store ready';
                    return 'store missing';
                },
                flagDefinitions: function () {
                    return [
                        {key: 'ai', label: 'AI'},
                        {key: 'alerts', label: 'Alerts'},
                        {key: 'widget', label: 'Widget'},
                        {key: 'ton_connect', label: 'TON Connect'},
                        {key: 'gamification', label: 'Gamification'},
                        {key: 'referrals', label: 'Referrals'},
                        {key: 'premium', label: 'Premium'}
                    ];
                },
                legalCopyDefinitions: function () {
                    return [
                        {key: 'market_data_disclaimer', label: 'Market data disclaimer'},
                        {key: 'ai_disclaimer', label: 'AI disclaimer'},
                        {key: 'alerts_disclaimer', label: 'Alerts disclaimer'},
                        {key: 'widget_disclaimer', label: 'Widget disclaimer'}
                    ];
                },
                coingeckoPlans: function () {
                    return ['demo', 'pro'];
                },
                providerStatusOptions: function () {
                    return ['not_configured', 'configured', 'enabled', 'degraded', 'disabled'];
                },
                cacheModeOptions: function () {
                    return ['serve_stale', 'strict', 'bypass'];
                },
                tonCategories: function () {
                    return ['native', 'stablecoin', 'jetton', 'defi', 'wallet', 'infrastructure', 'community'];
                },
                tonVerificationStates: function () {
                    return ['verified', 'curated', 'unverified'];
                },
                overviewMetrics: function () {
                    const flags = this.flagDefinitions.filter(flag => this.featureFlags[flag.key] === true).length;
                    const providers = [
                        _.get(this.providers, 'groq.api_key.configured'),
                        _.get(this.providers, 'coingecko.api_key.configured'),
                        _.get(this.providers, 'upstash.rest_token.configured'),
                        _.get(this.providers, 'telegram.bot_token.configured')
                    ].filter(Boolean).length;

                    return [
                        {label: 'Enabled flags', value: String(flags)},
                        {label: 'Configured secrets', value: String(providers)},
                        {label: 'TON assets', value: String((this.content.ton_assets || []).length)},
                        {label: 'Audit entries', value: String((this.auditLog || []).length)}
                    ];
                },
                cacheStatusLabel: function () {
                    const cache = this.operations.cache || {};
                    if (!cache.last_purge_at) return 'Purge status: ' + (cache.purge_status || 'idle');
                    return 'Purge status: ' + cache.purge_status + ' at ' + this.formatDate(cache.last_purge_at);
                },
                auditHeaders: function () {
                    return [
                        {text: 'Time', value: 'created_at'},
                        {text: 'Actor', value: 'actor'},
                        {text: 'Action', value: 'action'},
                        {text: 'Subject', value: 'subject_type'},
                        {text: 'Request', value: 'request_id'}
                    ];
                }
            },
            methods: {
                client: function () {
                    return axios.create({
                        baseURL: options.apiBaseUrl || '/api/admin',
                        headers: this.token ? {Authorization: 'Bearer ' + this.token} : {}
                    });
                },
                login: function () {
                    if (!this.loginToken || this.loading) return;

                    this.loading = true;
                    this.notice = '';
                    axios.post((options.apiBaseUrl || '/api/admin') + '/session', {token: this.loginToken})
                        .then(response => {
                            this.token = this.loginToken;
                            localStorage.setItem(tokenStorageKey, this.token);
                            this.actor = _.get(response, 'data.data.actor') || {};
                            this.loginToken = '';
                            return this.loadConfig();
                        })
                        .catch(error => this.handleError(error, 'Admin access failed.'))
                        .finally(() => this.loading = false);
                },
                logout: function () {
                    localStorage.removeItem(tokenStorageKey);
                    this.token = '';
                    this.loginToken = '';
                    this.actor = {};
                    this.meta = {};
                    this.auditLog = [];
                    this.notice = '';
                },
                loadConfig: function () {
                    if (!this.token) return Promise.resolve();

                    this.loading = true;
                    return this.client().get('/config')
                        .then(response => {
                            this.applyConfig(_.get(response, 'data.data') || {});
                            this.showNotice('Admin configuration loaded.', 'success');
                        })
                        .catch(error => {
                            if (_.get(error, 'response.status') === 401) this.logout();
                            this.handleError(error, 'Admin configuration could not be loaded.');
                        })
                        .finally(() => this.loading = false);
                },
                applyConfig: function (data) {
                    const providers = _.merge(defaultProviders(), clone(data.providers));
                    const content = _.merge(defaultContent(), clone(data.content));
                    const operations = _.merge(defaultOperations(), clone(data.operations));

                    content.ton_assets = (content.ton_assets || []).map((asset, index) => {
                        asset.local_id = asset.local_id || ('ton-asset-' + index + '-' + Date.now());
                        asset.tags = Array.isArray(asset.tags) ? asset.tags : [];
                        return asset;
                    });

                    this.actor = clone(data.actor || this.actor);
                    this.meta = clone(data.meta || {});
                    this.featureFlags = clone(data.feature_flags || {});
                    this.providers = providers;
                    this.content = content;
                    this.operations = operations;
                    this.auditLog = clone(data.audit_log || []);
                    this.clearProviderSecrets();
                },
                clearProviderSecrets: function () {
                    this.providerSecrets = {
                        coingecko: {api_key: ''},
                        groq: {api_key: ''},
                        upstash: {rest_token: ''},
                        telegram: {bot_token: ''}
                    };
                },
                saveFeatureFlags: function () {
                    if (!this.canWrite) return;
                    this.write('/feature-flags', {feature_flags: this.featureFlags}, 'Feature flags saved.')
                        .then(data => {
                            if (data.feature_flags) this.featureFlags = clone(data.feature_flags);
                        });
                },
                saveProviders: function () {
                    if (!this.canWrite) return;

                    const payload = {
                        coingecko: {
                            api_plan: _.get(this.providers, 'coingecko.api_plan')
                        },
                        groq: {
                            model_id: _.get(this.providers, 'groq.model_id')
                        },
                        upstash: {
                            status: _.get(this.providers, 'upstash.status')
                        },
                        telegram: {
                            bot_username: _.get(this.providers, 'telegram.bot_username')
                        },
                        changenow: {
                            link_id:     _.get(this.providers, 'changenow.link_id'),
                            listing_url: _.get(this.providers, 'changenow.listing_url')
                        }
                    };

                    if (this.providerSecrets.coingecko.api_key) payload.coingecko.api_key = this.providerSecrets.coingecko.api_key;
                    if (this.providerSecrets.groq.api_key) payload.groq.api_key = this.providerSecrets.groq.api_key;
                    if (this.providerSecrets.upstash.rest_token) payload.upstash.rest_token = this.providerSecrets.upstash.rest_token;
                    if (this.providerSecrets.telegram.bot_token) payload.telegram.bot_token = this.providerSecrets.telegram.bot_token;

                    this.write('/providers', {providers: payload}, 'Providers saved.')
                        .then(data => {
                            if (data.providers) this.providers = _.merge(defaultProviders(), clone(data.providers));
                            this.clearProviderSecrets();
                        });
                },
                saveContent: function () {
                    if (!this.canWrite) return;
                    const content = clone(this.content);
                    content.ton_assets = (content.ton_assets || []).map(asset => {
                        delete asset.local_id;
                        return asset;
                    });

                    this.write('/content', {content: content}, 'Content saved.')
                        .then(data => {
                            if (data.content) {
                                this.content = _.merge(defaultContent(), clone(data.content));
                                this.content.ton_assets = (this.content.ton_assets || []).map((asset, index) => {
                                    asset.local_id = asset.local_id || ('ton-asset-' + index + '-' + Date.now());
                                    return asset;
                                });
                            }
                        });
                },
                saveOperations: function () {
                    if (!this.canWrite) return;
                    this.write('/operations', {operations: this.operations}, 'Operations saved.')
                        .then(data => {
                            if (data.operations) this.operations = _.merge(defaultOperations(), clone(data.operations));
                        });
                },
                purgeCache: function () {
                    if (!this.canWrite) return;
                    this.saving = true;
                    this.client().post('/cache/purge', {scope: this.cacheScope || 'all'})
                        .then(response => {
                            const data = _.get(response, 'data.data') || {};
                            if (data.cache) this.operations.cache = clone(data.cache);
                            this.loadAuditLog();
                            this.showNotice('Cache purge requested.', 'success');
                        })
                        .catch(error => this.handleError(error, 'Cache purge could not be requested.'))
                        .finally(() => this.saving = false);
                },
                write: function (path, payload, message) {
                    this.saving = true;
                    this.notice = '';

                    return this.client().put(path, payload)
                        .then(response => {
                            const data = _.get(response, 'data.data') || {};
                            this.loadAuditLog();
                            this.showNotice(message, 'success');
                            return data;
                        })
                        .catch(error => {
                            this.handleError(error, 'Admin change could not be saved.');
                            return {};
                        })
                        .finally(() => this.saving = false);
                },
                loadAuditLog: function () {
                    if (!this.token) return;
                    this.client().get('/audit-log')
                        .then(response => {
                            this.auditLog = clone(_.get(response, 'data.data.audit_log') || []);
                        })
                        .catch(() => {});
                },
                addTonAsset: function () {
                    if (!this.canWrite) return;
                    this.content.ton_assets.push({
                        local_id: 'ton-asset-' + Date.now(),
                        id: '',
                        name: '',
                        symbol: '',
                        category: 'jetton',
                        verification_state: 'curated',
                        tags: ['ton_ecosystem']
                    });
                },
                removeTonAsset: function (index) {
                    if (!this.canWrite) return;
                    this.content.ton_assets.splice(index, 1);
                },
                secretStatus: function (metadata) {
                    if (_.get(metadata, 'configured')) return 'configured';
                    return 'not configured';
                },
                secretPlaceholder: function (metadata) {
                    if (_.get(metadata, 'configured')) return '[redacted]';
                    return '';
                },
                formatDate: function (value) {
                    const date = new Date(value || 0);
                    if (!GeckoClient.utils.isValidDate(date)) return 'N/A';
                    return date.toLocaleString();
                },
                showNotice: function (message, type) {
                    this.notice = message;
                    this.noticeType = type || 'info';
                },
                handleError: function (error, fallback) {
                    const message = _.get(error, 'response.data.error.message') || fallback;
                    this.showNotice(message, 'error');
                }
            }
        };
    }

    adminRouteNames.forEach(routeName => {
        const config = GeckoClient.routesConfig[routeName];
        if (!config) return;

        GeckoClient.router.addRoute({
            name: routeName,
            path: config.path,
            component: component()
        });
    });

})(window, _, axios, GeckoClient);

(function (window, _, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.alerts;
    if (!route) return;

    const options = GeckoClient.getOptions('alerts', {title: 'Alerts'});

    const component = {
        template: '#route-alerts',
        data: function () {
            return {
                rules: [],
                form: GeckoClient.alerts ? GeckoClient.alerts.emptyRule() : {},
                editingId: null,
                saving: false,
                testingId: null,
                notice: '',
                noticeModel: false,
                testResult: null,
                alertsUnsubscribe: null
            };
        },
        created: function () {
            GeckoClient.setTitle(options.title || 'Alerts');
            this.initAlerts();
        },
        beforeDestroy: function () {
            if (this.alertsUnsubscribe) this.alertsUnsubscribe();
        },
        watch: {
            '$route': function () {
                this.applyRouteContext();
            },
            'form.trigger_type': function (type) {
                if (this.form.threshold !== null && this.form.threshold !== '') return;
                this.form.threshold = this.defaultThreshold(type);
            }
        },
        computed: {
            featureEnabled: function () {
                return _.get(GeckoClient, 'runtime.features.alerts') === true;
            },
            storageModeLabel: function () {
                const mode = GeckoClient.alerts ? GeckoClient.alerts.storageMode : 'local';
                if (mode === 'server') return 'Telegram session';
                if (mode === 'memory') return 'Memory fallback';
                return 'Local draft';
            },
            activeCount: function () {
                return this.rules.filter(rule => rule.status === 'active').length;
            },
            pausedCount: function () {
                return this.rules.filter(rule => rule.status === 'paused').length;
            },
            sortedRules: function () {
                return this.rules.slice().sort((a, b) => {
                    const aTime = new Date(a.updated_at || a.created_at || 0).getTime() || 0;
                    const bTime = new Date(b.updated_at || b.created_at || 0).getTime() || 0;
                    return bTime - aTime;
                });
            },
            detailRule: function () {
                const id = _.get(this.$route, 'params.id');
                if (!id) return null;
                return _.find(this.rules, rule => String(rule.id) === String(id)) || null;
            },
            triggerTypes: function () {
                return [
                    {text: 'Price cross', value: 'price_cross'},
                    {text: 'Percent move', value: 'percent_move'},
                    {text: 'Volume spike', value: 'volume_spike'},
                    {text: 'Rank change', value: 'rank_change'},
                    {text: 'Sentiment change', value: 'sentiment_change'},
                    {text: 'Market cap cross', value: 'market_cap_cross'},
                    {text: 'TON ecosystem', value: 'ton_ecosystem'}
                ];
            },
            operatorOptions: function () {
                return [
                    {text: '>=', value: 'gte'},
                    {text: '>', value: 'gt'},
                    {text: '<=', value: 'lte'},
                    {text: '<', value: 'lt'}
                ];
            },
            thresholdLabel: function () {
                const labels = {
                    price_cross: 'Price threshold',
                    percent_move: '24h move percent',
                    volume_spike: '24h volume threshold',
                    rank_change: 'Rank threshold',
                    sentiment_change: 'Sentiment score',
                    market_cap_cross: 'Market cap threshold',
                    ton_ecosystem: 'TON move percent'
                };
                return labels[this.form.trigger_type] || 'Threshold';
            },
            saveLabel: function () {
                return this.editingId ? 'Update alert' : 'Create alert';
            },
            alertsShareCard: function () {
                return {
                    title: 'Alert wins',
                    subtitle: this.activeCount + ' active alerts',
                    body: 'Smart alert rules with Telegram delivery, test links, and coin context.',
                    route: '/alerts',
                    campaign: 'alert-wins',
                    context: 'alert_win',
                    freshness: 'Updated in browser session',
                    metrics: [
                        {label: 'Active', value: String(this.activeCount)},
                        {label: 'Paused', value: String(this.pausedCount)},
                        {label: 'Storage', value: this.storageModeLabel},
                        {label: 'Delivery', value: this.featureEnabled ? 'Enabled' : 'Flag off'}
                    ]
                };
            },
            testResultShareCard: function () {
                const route = _.get(this.testResult, 'links.mini_app_path') || '/alerts';

                return {
                    title: 'Alert win',
                    subtitle: 'Test delivery ready',
                    body: _.get(this.testResult, 'text') || 'A TONBANKCARD alert delivery is ready to open.',
                    route: route,
                    campaign: 'alert-wins',
                    context: 'alert_win',
                    freshness: 'Generated now',
                    metrics: [
                        {label: 'Route', value: route},
                        {label: 'Channel', value: 'Telegram bot'},
                        {label: 'Mode', value: 'Test delivery'},
                        {label: 'Storage', value: this.storageModeLabel}
                    ]
                };
            }
        },
        methods: {
            initAlerts: function () {
                if (!GeckoClient.alerts) return;

                this.alertsUnsubscribe = GeckoClient.alerts.onChange(snapshot => {
                    this.rules = snapshot.rules || [];
                });

                GeckoClient.alerts.init().then(() => {
                    this.rules = GeckoClient.alerts.list();
                    this.applyRouteContext();
                });
            },
            applyRouteContext: function () {
                const draft = GeckoClient.alerts ? GeckoClient.alerts.readDraft() : null;
                const queryCoin = _.get(this.$route, 'query.coin');

                if (draft) {
                    this.form = this.ruleToForm(Object.assign({}, draft, {
                        trigger_type: draft.trigger_type || 'price_cross',
                        threshold: draft.threshold || null,
                        context_path: draft.context_path || (draft.coin_id ? '/currency/' + draft.coin_id : null)
                    }));
                    if (GeckoClient.alerts) GeckoClient.alerts.clearDraft();
                    return;
                }

                if (queryCoin && !this.editingId) {
                    this.form.coin_id = queryCoin;
                    this.form.symbol = _.toUpper(_.get(this.$route, 'query.symbol') || this.form.symbol || '');
                    this.form.context_path = '/currency/' + queryCoin;
                }

                if (this.detailRule) {
                    this.startEdit(this.detailRule);
                }
            },
            ruleToForm: function (rule) {
                const normalized = GeckoClient.alerts.normalizeRule(rule) || GeckoClient.alerts.emptyRule();
                return Object.assign({}, normalized);
            },
            resetForm: function () {
                this.editingId = null;
                this.form = GeckoClient.alerts.emptyRule();
                this.testResult = null;
            },
            saveRule: function () {
                if (!GeckoClient.alerts || this.saving) return;

                this.saving = true;
                const payload = Object.assign({}, this.form, {
                    id: this.editingId || this.form.id,
                    context_path: this.form.context_path || '/currency/' + this.form.coin_id
                });

                GeckoClient.alerts.save(payload, {sourceRoute: 'alerts'})
                    .then(rule => {
                        this.rules = GeckoClient.alerts.list();
                        this.editingId = rule.id;
                        this.form = this.ruleToForm(rule);
                        this.showNotice((payload.id ? 'Updated' : 'Created') + ' alert for ' + this.ruleSymbol(rule) + '.');
                    })
                    .catch(() => {
                        this.showNotice('Alert could not be saved. Check the rule fields and Telegram session.');
                    })
                    .finally(() => this.saving = false);
            },
            startEdit: function (rule) {
                this.editingId = rule.id || rule.local_id;
                this.form = this.ruleToForm(rule);
                this.testResult = null;
            },
            pauseRule: function (rule) {
                if (!GeckoClient.alerts) return;
                GeckoClient.alerts.pause(rule, {sourceRoute: 'alerts'}).then(saved => {
                    this.rules = GeckoClient.alerts.list();
                    this.showNotice('Paused alert for ' + this.ruleSymbol(saved) + '.');
                });
            },
            resumeRule: function (rule) {
                if (!GeckoClient.alerts) return;
                GeckoClient.alerts.resume(rule, {sourceRoute: 'alerts'}).then(saved => {
                    this.rules = GeckoClient.alerts.list();
                    this.showNotice('Resumed alert for ' + this.ruleSymbol(saved) + '.');
                });
            },
            deleteRule: function (rule) {
                if (!GeckoClient.alerts) return;
                GeckoClient.alerts.remove(rule, {sourceRoute: 'alerts'}).then(() => {
                    this.rules = GeckoClient.alerts.list();
                    if (String(this.editingId) === String(rule.id || rule.local_id)) this.resetForm();
                    this.showNotice('Deleted alert for ' + this.ruleSymbol(rule) + '.');
                });
            },
            testRule: function (rule) {
                if (!GeckoClient.alerts) return;
                this.testingId = rule.id || rule.local_id || 'form';
                GeckoClient.alerts.test(rule, {sourceRoute: 'alerts'})
                    .then(delivery => {
                        this.testResult = delivery;
                        this.showNotice('Test alert prepared for ' + this.ruleSymbol(rule) + '.');
                    })
                    .catch(() => {
                        this.showNotice('Test alert could not be prepared.');
                    })
                    .finally(() => this.testingId = null);
            },
            testCurrentForm: function () {
                const rule = GeckoClient.alerts.normalizeRule(this.form);
                if (rule) this.testRule(rule);
            },
            defaultThreshold: function (type) {
                const defaults = {
                    price_cross: 1,
                    percent_move: 5,
                    volume_spike: 1000000,
                    rank_change: 10,
                    sentiment_change: 50,
                    market_cap_cross: 1000000000,
                    ton_ecosystem: 5
                };
                return defaults[type] || 1;
            },
            ruleSymbol: function (rule) {
                return rule.symbol || rule.display_name || _.startCase(rule.coin_id || 'asset');
            },
            triggerTypeLabel: function (type) {
                const item = _.find(this.triggerTypes, ['value', type]);
                return item ? item.text : _.startCase(type || '');
            },
            operatorLabel: function (operator) {
                const item = _.find(this.operatorOptions, ['value', operator]);
                return item ? item.text : operator;
            },
            capLabel: function (seconds) {
                seconds = parseInt(seconds || 0, 10);
                if (seconds >= 3600) return Math.round(seconds / 3600) + 'h cap';
                return Math.round(seconds / 60) + 'm cap';
            },
            deliveryPath: function (rule) {
                return _.get(rule, 'links.mini_app_path') || '/app/alerts?coin=' + encodeURIComponent(rule.coin_id);
            },
            alertShareCard: function (rule) {
                if (!rule) return this.alertsShareCard;

                const symbol = this.ruleSymbol(rule);
                const route = rule.id ? '/app/alert/' + encodeURIComponent(rule.id) : this.deliveryPath(rule);

                return {
                    title: symbol + ' alert win',
                    subtitle: this.triggerTypeLabel(rule.trigger_type),
                    body: symbol + ' alert ' + this.operatorLabel(rule.operator) + ' ' + rule.threshold + ' on TONBANKCARD.',
                    route: route,
                    campaign: 'alert-wins',
                    context: 'alert_win',
                    freshness: rule.updated_at ? 'Updated ' + this.relativeTime(rule.updated_at) : 'Saved alert rule',
                    metrics: [
                        {label: 'Coin', value: rule.coin_id || symbol},
                        {label: 'Trigger', value: this.triggerTypeLabel(rule.trigger_type)},
                        {label: 'Status', value: rule.status || 'active'},
                        {label: 'Cap', value: this.capLabel(rule.frequency_cap_seconds)}
                    ]
                };
            },
            shareAlert: function (ruleOrCard) {
                if (!GeckoClient.share) return;

                const card = ruleOrCard && ruleOrCard.context ? ruleOrCard : this.alertShareCard(ruleOrCard);
                GeckoClient.share.share(card);
            },
            openCoinRoute: function (rule) {
                return {name: 'currency', params: {id: rule.coin_id}};
            },
            relativeTime: function (timestamp) {
                const date = new Date(timestamp);
                if (!GeckoClient.utils.isValidDate(date)) return 'unknown';

                const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
                if (seconds < 60) return 'now';
                if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                return Math.floor(seconds / 86400) + 'd ago';
            },
            showNotice: function (message) {
                this.notice = message;
                this.noticeModel = true;
            }
        }
    };

    GeckoClient.router.addRoute({
        name: 'alerts',
        path: route.path,
        component: component
    });

    if (GeckoClient.routesConfig['app-alerts']) {
        GeckoClient.router.addRoute({
            name: 'app-alerts',
            path: GeckoClient.routesConfig['app-alerts'].path,
            component: component
        });
    }

    if (GeckoClient.routesConfig['app-alert-detail']) {
        GeckoClient.router.addRoute({
            name: 'app-alert-detail',
            path: GeckoClient.routesConfig['app-alert-detail'].path,
            component: component
        });
    }

})(window, _, GeckoClient);

(function (window, _, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.achievements;
    if (!route) return;

    const options = GeckoClient.getOptions('achievements', {title: 'Achievements'});

    GeckoClient.router.addRoute({
        name: 'achievements',
        path: route.path,
        component: {
            template: '#route-achievements',
            data: function () {
                return {
                    snapshotData: this.emptySnapshot(),
                    achievementsUnsubscribe: null,
                    sharingId: '',
                    notice: ''
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title || 'Achievements');
                this.initAchievements();
            },
            beforeDestroy: function () {
                if (this.achievementsUnsubscribe) this.achievementsUnsubscribe();
            },
            computed: {
                featureEnabled: function () {
                    return _.get(this.snapshotData, 'settings.enabled') === true;
                },
                optInModel: {
                    get: function () {
                        return this.snapshotData.opted_in === true;
                    },
                    set: function (value) {
                        this.toggleOptIn(value);
                    }
                },
                definitions: function () {
                    return this.snapshotData.definitions || [];
                },
                achievements: function () {
                    return this.snapshotData.achievements || {};
                },
                counters: function () {
                    return this.snapshotData.counters || {};
                },
                streak: function () {
                    return this.snapshotData.streak || {};
                },
                unlockedCount: function () {
                    return this.snapshotData.unlocked_count || 0;
                },
                activePrompts: function () {
                    return this.definitions.filter(definition => {
                        return _.get(this.achievements, [definition.id, 'prompt_state']) === 'active';
                    });
                },
                progressShareCard: function () {
                    return GeckoClient.achievements ? GeckoClient.achievements.progressShareCard() : null;
                }
            },
            methods: {
                emptySnapshot: function () {
                    return {
                        opted_in: false,
                        settings: {},
                        definitions: [],
                        achievements: {},
                        dismissed: {},
                        counters: {},
                        streak: {},
                        events: [],
                        unlocked_count: 0
                    };
                },
                initAchievements: function () {
                    if (!GeckoClient.achievements) return;

                    this.achievementsUnsubscribe = GeckoClient.achievements.onChange(snapshot => {
                        this.snapshotData = snapshot || this.emptySnapshot();
                    });

                    GeckoClient.achievements.init().then(() => {
                        this.snapshotData = GeckoClient.achievements.snapshot();
                    });
                },
                achievement: function (definition) {
                    return _.get(this.achievements, definition.id, null);
                },
                isUnlocked: function (definition) {
                    return !!this.achievement(definition);
                },
                badgeColor: function (definition) {
                    if (!this.isUnlocked(definition)) return 'grey';
                    if (definition.category === 'streak') return 'primary';
                    if (definition.category === 'ton_ecosystem') return 'deep-purple';
                    if (definition.category === 'sharing') return 'success';
                    if (definition.category === 'market_awareness') return 'warning';
                    return 'info';
                },
                progressValue: function (definition) {
                    if (this.isUnlocked(definition)) return 100;
                    const threshold = parseFloat(definition.threshold) || 1;
                    let value = 0;

                    if (definition.id === 'weekly_market_check') value = parseInt(this.streak.current_count, 10) || 0;
                    else if (definition.id === 'caught_market_movement') value = parseInt(this.counters.market_movement_caught, 10) || 0;
                    else value = parseInt(this.counters[definition.trigger], 10) || 0;

                    return Math.max(0, Math.min(100, Math.round((value / threshold) * 100)));
                },
                progressText: function (definition) {
                    if (this.isUnlocked(definition)) return 'Unlocked';
                    if (!this.featureEnabled) return 'Flag off';
                    if (!this.optInModel) return 'Opt-in';
                    if (definition.id === 'weekly_market_check') {
                        return (this.streak.current_count || 0) + '/' + definition.threshold + ' days';
                    }
                    if (definition.id === 'caught_market_movement') {
                        return (this.counters.market_movement_caught || 0) + '/1 move';
                    }
                    return (this.counters[definition.trigger] || 0) + '/' + definition.threshold;
                },
                toggleOptIn: function (enabled) {
                    if (!GeckoClient.achievements || !this.featureEnabled) return;
                    this.snapshotData = enabled ? GeckoClient.achievements.enable() : GeckoClient.achievements.disable();
                },
                dismissPrompt: function (definition) {
                    if (!GeckoClient.achievements) return;
                    this.snapshotData = GeckoClient.achievements.dismiss(definition.id);
                },
                shareAchievement: function (definition) {
                    if (!GeckoClient.achievements || !GeckoClient.share) return;
                    const card = GeckoClient.achievements.achievementShareCard(definition.id);
                    if (!card) return;

                    this.sharingId = definition.id;
                    GeckoClient.share.share(card)
                        .then(shared => {
                            if (shared) {
                                GeckoClient.achievements.markShared(definition.id);
                                this.notice = 'Achievement card ready';
                            }
                        })
                        .finally(() => this.sharingId = '');
                },
                shareProgress: function () {
                    if (!GeckoClient.share || !this.progressShareCard) return;
                    GeckoClient.share.share(this.progressShareCard);
                },
                unlockedLabel: function (definition) {
                    const achievement = this.achievement(definition);
                    if (!achievement) return 'Locked';
                    return 'Unlocked ' + this.relativeDate(achievement.unlocked_at);
                },
                relativeDate: function (timestamp) {
                    const date = new Date(timestamp);
                    if (!GeckoClient.utils.isValidDate(date)) return '';
                    return date.toISOString().slice(0, 10);
                }
            }
        }
    });

})(window, _, GeckoClient);

(function (window, _, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.premium;
    const appRoute = GeckoClient.routesConfig['app-premium'];
    if (!route && !appRoute) return;

    const options = GeckoClient.getOptions('premium', {title: 'Premium'});

    function createComponent(templateId) {
        return {
            template: templateId,
            data: function () {
                return {
                    plans: GeckoClient.premium ? GeckoClient.premium.plans : [],
                    settings: GeckoClient.premium ? GeckoClient.premium.settings : {},
                    entitlement: GeckoClient.premium ? GeckoClient.premium.entitlement() : {},
                    loading: false,
                    canceling: false,
                    checkoutPlanCode: '',
                    notice: '',
                    noticeType: 'info'
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title || 'Premium');
                this.initPremium();
            },
            computed: {
                checkoutEnabled: function () {
                    return this.settings.checkout_enabled === true;
                },
                entitlementLabel: function () {
                    if (_.get(this.entitlement, 'entitled') === true) return 'Premium active';
                    return 'Free limits';
                },
                currentPlanCode: function () {
                    return _.get(this.entitlement, 'plan_code') || 'free';
                },
                entitlementExpiresLabel: function () {
                    const expiresAt = _.get(this.entitlement, 'entitlement.expires_at');
                    if (!expiresAt) return 'No expiry';
                    const date = new Date(expiresAt);
                    return GeckoClient.utils.isValidDate(date) ? date.toISOString().slice(0, 10) : expiresAt;
                },
                priorityRefreshLabel: function () {
                    return _.get(this.entitlement, 'limits.priority_refresh') === true ? 'Enabled' : 'Standard';
                },
                canCancel: function () {
                    return _.get(this.entitlement, 'entitled') === true && _.get(this.entitlement, 'entitlement.cancel_at_period_end') !== true;
                }
            },
            methods: {
                initPremium: function () {
                    if (!GeckoClient.premium) return;
                    this.loading = true;
                    GeckoClient.premium.init()
                        .then(() => GeckoClient.premium.fetchPlans())
                        .then(result => {
                            this.settings = result.settings || this.settings;
                            this.plans = result.plans || this.plans;
                            this.entitlement = GeckoClient.premium.entitlement();
                        })
                        .catch(() => {
                            this.showNotice('Premium status could not be loaded.', 'warning');
                        })
                        .finally(() => this.loading = false);
                },
                refreshEntitlement: function () {
                    if (!GeckoClient.premium) return;
                    this.loading = true;
                    GeckoClient.premium.fetchEntitlement()
                        .then(entitlement => {
                            this.entitlement = entitlement;
                            this.showNotice('Premium status refreshed.', 'success');
                        })
                        .catch(() => this.showNotice('Premium status could not be refreshed.', 'warning'))
                        .finally(() => this.loading = false);
                },
                startCheckout: function (plan) {
                    if (!GeckoClient.premium || !plan || this.checkoutPlanCode) return;
                    this.checkoutPlanCode = plan.code;
                    GeckoClient.premium.checkout(plan.code)
                        .then(data => {
                            if (data.invoice_link) {
                                GeckoClient.premium.openInvoice(data.invoice_link);
                                this.showNotice('Telegram Stars invoice opened.', 'success');
                            } else {
                                this.showNotice('Invoice link was not returned.', 'warning');
                            }
                        })
                        .catch(() => this.showNotice('Stars checkout could not be started. Check your Telegram session.', 'warning'))
                        .finally(() => this.checkoutPlanCode = '');
                },
                cancelEntitlement: function () {
                    if (!GeckoClient.premium || !this.canCancel || this.canceling) return;
                    this.canceling = true;
                    GeckoClient.premium.cancel()
                        .then(entitlement => {
                            this.entitlement = entitlement;
                            this.showNotice('Premium renewal cancellation recorded.', 'success');
                        })
                        .catch(() => this.showNotice('Premium renewal could not be canceled.', 'warning'))
                        .finally(() => this.canceling = false);
                },
                showNotice: function (message, type) {
                    this.notice = message;
                    this.noticeType = type || 'info';
                },
                isCurrentPlan: function (plan) {
                    return plan && plan.code === this.currentPlanCode;
                },
                priceLabel: function (plan) {
                    if (!plan || !plan.price_stars) return 'Free';
                    return plan.price_stars + ' Stars';
                },
                rangeLabel: function (ranges) {
                    return (ranges || []).map(range => String(range).toUpperCase()).join(', ');
                },
                secondsLabel: function (seconds) {
                    seconds = parseInt(seconds, 10) || 0;
                    if (seconds <= 0) return 'Standard';
                    if (seconds < 60) return seconds + 's';
                    if (seconds < 3600) return Math.round(seconds / 60) + 'm';
                    return Math.round(seconds / 3600) + 'h';
                },
                limitRows: function (plan) {
                    const limits = _.get(plan, 'limits', {});
                    return [
                        {key: 'alerts', icon: 'mdi-bell-outline', label: 'Alerts', value: (limits.alerts_per_user || 0) + ' rules'},
                        {key: 'watchlist', icon: 'mdi-star-outline', label: 'Watchlist', value: (limits.watchlist_entries || 0) + ' assets'},
                        {key: 'ranges', icon: 'mdi-chart-timeline-variant', label: 'Ranges', value: this.rangeLabel(limits.advanced_ranges)},
                        {key: 'digest', icon: 'mdi-brain', label: 'AI digest', value: (limits.ai_digest_per_day || 0) + '/day'},
                        {key: 'refresh', icon: 'mdi-refresh', label: 'Refresh', value: limits.priority_refresh ? this.secondsLabel(limits.market_refresh_seconds) : 'Standard'}
                    ];
                }
            }
        };
    }

    if (route) {
        GeckoClient.router.addRoute({
            name: 'premium',
            path: route.path,
            component: createComponent('#route-premium')
        });
    }

    if (appRoute) {
        GeckoClient.router.addRoute({
            name: 'app-premium',
            path: appRoute.path,
            component: createComponent('#route-app-premium')
        });
    }

})(window, _, GeckoClient);

(function (window, _, axios, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.screener;
    const options = GeckoClient.getOptions('screener');
    if (!route) return;

    const numericFilterKeys = [
        'market_cap_min',
        'market_cap_max',
        'volume_min',
        'volume_max',
        'rank_min',
        'rank_max',
        'change_24h_min',
        'change_24h_max',
        'change_7d_min',
        'change_7d_max',
        'change_30d_min',
        'change_30d_max',
        'sentiment_min',
        'sentiment_max'
    ];
    const textFilterKeys = ['category', 'exchange', 'ton_tag', 'watchlist'];
    const sortAliases = {
        change_24h: 'price_change_percentage_24h_in_currency',
        change_7d: 'price_change_percentage_7d_in_currency',
        change_30d: 'price_change_percentage_30d_in_currency',
        sentiment: 'sentiment_score'
    };
    const allowedSortKeys = [
        'market_cap_rank',
        'name',
        'current_price',
        'market_cap',
        'total_volume',
        'price_change_percentage_24h_in_currency',
        'price_change_percentage_7d_in_currency',
        'price_change_percentage_30d_in_currency',
        'sentiment_score',
        'exchange_available'
    ];

    function defaultFilters() {
        const filters = {
            category: '',
            exchange: '',
            ton_tag: '',
            watchlist: 'all'
        };
        numericFilterKeys.forEach(key => filters[key] = '');
        return filters;
    }

    function normalizeSlug(value) {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    }

    function normalizeWatchlist(value) {
        value = normalizeSlug(value || 'all');
        return _.includes(['all', 'watched', 'unwatched'], value) ? value : 'all';
    }

    function normalizeSort(value) {
        value = _.isArray(value) ? _.first(value) : value;
        value = _.toString(value || 'market_cap_rank');
        value = sortAliases[value] || value;
        return _.includes(allowedSortKeys, value) ? value : 'market_cap_rank';
    }

    function normalizeDirection(value) {
        if (_.isArray(value)) value = _.first(value);
        return value === true || value === 'desc' ? 'desc' : 'asc';
    }

    function cleanNumber(value) {
        value = _.toString(value === null || value === undefined ? '' : value).trim();
        if (!value) return '';
        const number = parseFloat(value);
        return _.isFinite(number) ? String(number) : '';
    }

    function responseData(response) {
        const payload = response && response.data ? response.data : {};
        return payload.ok === true ? payload.data || {} : payload;
    }

    function telegramInitData() {
        return _.get(GeckoClient, 'telegram.webApp.initData') || _.get(window, 'Telegram.WebApp.initData') || '';
    }

    function asArray(value) {
        return _.isArray(value) ? value : [];
    }

    GeckoClient.router.addRoute({
        name: 'screener',
        path: route.path,
        component: {
            template: '#route-screener',
            data: function () {
                return {
                    items: [],
                    summary: {},
                    filterOptions: {},
                    filters: defaultFilters(),
                    sortBy: 'market_cap_rank',
                    sortDesc: false,
                    loading: false,
                    errorMessage: '',
                    filterDrawer: false,
                    watchlistIds: [],
                    watchlistUnsubscribe: null,
                    presets: [],
                    selectedPresetId: null,
                    presetName: '',
                    presetSyncEnabled: false,
                    presetSyncChecked: false,
                    presetSyncError: '',
                    loadingPresets: false,
                    savingPreset: false,
                    deletingPreset: false
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.syncFiltersFromRoute();
                this.initWatchlist();
                this.bootstrapPresetSync();
                this.fetchScreenerResults();
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            watch: {
                '$route.query': function () {
                    this.syncFiltersFromRoute();
                    this.fetchScreenerResults();
                },
                '$root.vsCurrencyId': function () {
                    this.fetchScreenerResults();
                }
            },
            computed: {
                tableHeaders: function () {
                    const headers = asArray(options.tableHeaders).filter(header => header.show !== false);
                    if (this.$vuetify.breakpoint.xs) {
                        return _.reject(headers, header => _.includes(['market_cap_rank', 'exchange_available'], header.value));
                    }
                    return headers;
                },
                categoryOptions: function () {
                    const configured = asArray(options.categoryOptions);
                    const responseCategories = asArray(this.filterOptions.categories).map(category => ({
                        text: category.title || _.startCase(category.id || category.slug || category.name || ''),
                        value: category.id || category.slug || category.name || ''
                    })).filter(category => category.value);
                    return configured.length ? configured : [{text: 'All categories', value: ''}].concat(responseCategories);
                },
                exchangeOptions: function () {
                    const configured = asArray(options.exchangeOptions);
                    const responseExchanges = asArray(this.filterOptions.exchanges).map(exchange => ({
                        text: exchange.name || _.startCase(exchange.id || ''),
                        value: exchange.id || ''
                    })).filter(exchange => exchange.value);
                    return configured.length ? configured : [{text: 'Any exchange', value: ''}].concat(responseExchanges);
                },
                tonTagOptions: function () {
                    const configured = asArray(options.tonTagOptions);
                    const responseTags = asArray(this.filterOptions.ton_tags).map(tag => ({
                        text: _.startCase(tag),
                        value: tag
                    }));
                    return configured.length ? configured : [{text: 'All assets', value: ''}].concat(responseTags);
                },
                watchlistFilterOptions: function () {
                    return [
                        {text: 'All assets', value: 'all'},
                        {text: 'Watched only', value: 'watched'},
                        {text: 'Unwatched only', value: 'unwatched'}
                    ];
                },
                hasActiveFilters: function () {
                    return this.activeFilterChips.length > 0;
                },
                activeFilterChips: function () {
                    const labels = {
                        category: 'Category',
                        exchange: 'Exchange',
                        ton_tag: 'TON',
                        watchlist: 'Watchlist',
                        market_cap_min: 'MCap min',
                        market_cap_max: 'MCap max',
                        volume_min: 'Volume min',
                        volume_max: 'Volume max',
                        rank_min: 'Rank min',
                        rank_max: 'Rank max',
                        change_24h_min: '24h min',
                        change_24h_max: '24h max',
                        change_7d_min: '7d min',
                        change_7d_max: '7d max',
                        change_30d_min: '30d min',
                        change_30d_max: '30d max',
                        sentiment_min: 'Sentiment min',
                        sentiment_max: 'Sentiment max'
                    };
                    const chips = [];
                    Object.keys(labels).forEach(key => {
                        const value = this.filters[key];
                        if (!value || (key === 'watchlist' && value === 'all')) return;
                        chips.push({
                            key: key,
                            icon: key === 'watchlist' ? 'mdi-star-outline' : (key === 'ton_tag' ? 'mdi-diamond-stone' : 'mdi-filter-outline'),
                            label: labels[key] + ': ' + this.displayFilterValue(key, value)
                        });
                    });
                    return chips;
                },
                emptyFilterSummary: function () {
                    if (this.hasActiveFilters) {
                        return 'No assets match the active screener filters: ' + this.activeFilterChips.map(chip => chip.label).join(', ') + '.';
                    }
                    return 'No market rows are available from the backend dataset right now.';
                },
                resultSummaryText: function () {
                    const sourceCount = parseInt(this.summary.source_count || 0, 10);
                    const resultCount = parseInt(this.summary.result_count || this.items.length || 0, 10);
                    return resultCount + ' matches from ' + sourceCount + ' backend rows';
                },
                sentimentSummary: function () {
                    if (this.summary.average_sentiment === null || this.summary.average_sentiment === undefined) return 'n/a';
                    const score = parseInt(this.summary.average_sentiment, 10);
                    return (_.isFinite(score) && score > 0 ? '+' : '') + score;
                },
                presetOptions: function () {
                    return this.presets.map(preset => ({
                        text: preset.name,
                        value: preset.id
                    }));
                },
                presetStatusText: function () {
                    if (this.loadingPresets) return 'Loading presets';
                    if (this.presetSyncEnabled) return this.presets.length ? 'Trusted preset sync active' : 'Trusted preset sync active; no presets saved';
                    if (this.presetSyncError) return this.presetSyncError;
                    return this.presetSyncChecked ? 'Preset sync requires a trusted Telegram session' : 'Checking preset sync';
                }
            },
            methods: {
                endpoint: function () {
                    return options.apiBaseUrl || '/api/screener/markets';
                },
                presetsEndpoint: function () {
                    return options.presetsApiBaseUrl || '/api/screener/presets';
                },
                syncFiltersFromRoute: function () {
                    const query = this.$route.query || {};
                    const next = defaultFilters();

                    textFilterKeys.forEach(key => {
                        if (key === 'watchlist') next[key] = normalizeWatchlist(query[key] || 'all');
                        else next[key] = normalizeSlug(query[key] || '');
                    });
                    numericFilterKeys.forEach(key => {
                        next[key] = cleanNumber(query[key]);
                    });

                    this.filters = next;
                    this.sortBy = normalizeSort(query.sort);
                    this.sortDesc = normalizeDirection(query.direction) === 'desc';
                },
                buildQuery: function () {
                    const query = {};
                    textFilterKeys.forEach(key => {
                        const value = key === 'watchlist' ? normalizeWatchlist(this.filters[key]) : normalizeSlug(this.filters[key]);
                        if (value && !(key === 'watchlist' && value === 'all')) query[key] = value;
                    });
                    numericFilterKeys.forEach(key => {
                        const value = cleanNumber(this.filters[key]);
                        if (value) query[key] = value;
                    });

                    const sort = normalizeSort(this.sortBy);
                    const direction = this.sortDesc ? 'desc' : 'asc';
                    if (sort !== 'market_cap_rank') query.sort = sort;
                    if (direction !== 'asc') query.direction = direction;
                    return query;
                },
                requestParams: function () {
                    const params = Object.assign({}, this.buildQuery(), {
                        vs_currency: this.$root.vsCurrencyId,
                        per_page: options.perPage || 100,
                        sort: normalizeSort(this.sortBy),
                        direction: this.sortDesc ? 'desc' : 'asc'
                    });
                    if (this.watchlistIds.length) {
                        params.watchlist_ids = this.watchlistIds.join(',');
                    }
                    return params;
                },
                updateRouteFromState: function () {
                    const query = this.buildQuery();
                    const current = this.$route.query || {};
                    if (_.isEqual(query, current)) {
                        this.fetchScreenerResults();
                        return;
                    }
                    this.$router.replace({name: 'screener', query: query}).catch(() => {});
                },
                applyFilters: function () {
                    this.filterDrawer = false;
                    this.updateRouteFromState();
                },
                resetFilters: function () {
                    this.filters = defaultFilters();
                    this.sortBy = 'market_cap_rank';
                    this.sortDesc = false;
                    this.applyFilters();
                },
                clearFilter: function (key) {
                    if (key === 'watchlist') this.filters[key] = 'all';
                    else if (_.has(this.filters, key)) this.filters[key] = '';
                    this.applyFilters();
                },
                updateSort: function (value) {
                    const next = normalizeSort(value);
                    if (next === this.sortBy) return;
                    this.sortBy = next;
                    this.applyFilters();
                },
                updateSortDirection: function (value) {
                    const next = normalizeDirection(value) === 'desc';
                    if (next === this.sortDesc) return;
                    this.sortDesc = next;
                    this.applyFilters();
                },
                fetchScreenerResults: function () {
                    if (!axios) return Promise.resolve();

                    this.loading = true;
                    this.errorMessage = '';

                    return axios.get(this.endpoint(), {
                        params: this.requestParams(),
                        withCredentials: true
                    }).then(response => {
                        const payload = responseData(response);
                        this.items = asArray(payload.items).map(item => Object.assign({}, item, {
                            route: this.currencyRoute(item)
                        }));
                        this.summary = payload.summary || {};
                        this.filterOptions = payload.filter_options || {};
                    }).catch(() => {
                        this.items = [];
                        this.summary = {};
                        this.errorMessage = 'Screener data is temporarily unavailable.';
                    }).finally(() => {
                        this.loading = false;
                    });
                },
                initWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(() => {
                        this.syncWatchlistIds();
                        this.fetchScreenerResults();
                    });
                    watchlist.init().then(() => {
                        this.syncWatchlistIds();
                        this.fetchScreenerResults();
                    });
                },
                syncWatchlistIds: function () {
                    this.watchlistIds = GeckoClient.watchlist ? GeckoClient.watchlist.ids() : [];
                },
                isWatched: function (item) {
                    const id = item && (item.id || item.coin_id);
                    return this.watchlistIds.indexOf(id) >= 0 || _.get(item, 'watchlist.watched') === true;
                },
                watchlistIcon: function (item) {
                    return this.isWatched(item) ? 'mdi-star' : 'mdi-star-outline';
                },
                watchlistLabel: function (item) {
                    const name = _.get(item, 'name', 'asset');
                    return (this.isWatched(item) ? 'Remove ' : 'Add ') + name + ' ' + (this.isWatched(item) ? 'from' : 'to') + ' Watchlist';
                },
                toggleWatchlist: function (item) {
                    if (!GeckoClient.watchlist) return;

                    GeckoClient.watchlist.toggle(item, {sourceRoute: 'screener'})
                        .then(() => {
                            this.syncWatchlistIds();
                            this.fetchScreenerResults();
                        });
                },
                bootstrapPresetSync: function () {
                    const initData = telegramInitData();
                    if (!initData || !axios) {
                        this.presetSyncChecked = true;
                        this.presetSyncError = 'Preset sync requires a trusted Telegram session';
                        return Promise.resolve(false);
                    }

                    return axios.post('/api/telegram/session', {initData: initData}, {withCredentials: true})
                        .then(response => {
                            const payload = response.data || {};
                            this.presetSyncEnabled = payload.ok === true && _.get(payload, 'data.session.state') === 'telegram_validated';
                            this.presetSyncChecked = true;
                            if (this.presetSyncEnabled) {
                                return this.fetchPresets();
                            }
                            this.presetSyncError = 'Preset sync requires a trusted Telegram session';
                            return false;
                        })
                        .catch(() => {
                            this.presetSyncChecked = true;
                            this.presetSyncEnabled = false;
                            this.presetSyncError = 'Preset sync is unavailable';
                            return false;
                        });
                },
                fetchPresets: function () {
                    if (!this.presetSyncEnabled || !axios) return Promise.resolve();

                    this.loadingPresets = true;
                    return axios.get(this.presetsEndpoint(), {withCredentials: true})
                        .then(response => {
                            const payload = responseData(response);
                            this.presets = asArray(payload.presets);
                        })
                        .catch(() => {
                            this.presets = [];
                            this.presetSyncError = 'Preset sync is unavailable';
                        })
                        .finally(() => this.loadingPresets = false);
                },
                savePreset: function () {
                    if (!this.presetSyncEnabled || !this.presetName || !axios) return;

                    this.savingPreset = true;
                    return axios.post(
                        this.presetsEndpoint(),
                        {
                            name: this.presetName,
                            filters: this.requestParams(),
                            sort: {
                                key: normalizeSort(this.sortBy),
                                direction: this.sortDesc ? 'desc' : 'asc'
                            }
                        },
                        {withCredentials: true}
                    ).then(response => {
                        const payload = responseData(response);
                        this.presets = asArray(payload.presets);
                        if (payload.preset && payload.preset.id) {
                            this.selectedPresetId = payload.preset.id;
                        }
                    }).catch(() => {
                        this.presetSyncError = 'Preset could not be saved';
                    }).finally(() => this.savingPreset = false);
                },
                deletePreset: function () {
                    if (!this.presetSyncEnabled || !this.selectedPresetId || !axios) return;

                    this.deletingPreset = true;
                    return axios.delete(this.presetsEndpoint() + '/' + encodeURIComponent(this.selectedPresetId), {withCredentials: true})
                        .then(response => {
                            const payload = responseData(response);
                            this.presets = asArray(payload.presets);
                            this.selectedPresetId = null;
                            this.presetName = '';
                        })
                        .catch(() => {
                            this.presetSyncError = 'Preset could not be deleted';
                        })
                        .finally(() => this.deletingPreset = false);
                },
                applyPreset: function (id) {
                    const preset = _.find(this.presets, preset => String(preset.id) === String(id));
                    if (!preset) return;

                    const filters = defaultFilters();
                    const presetFilters = preset.filters || {};
                    textFilterKeys.forEach(key => {
                        filters[key] = key === 'watchlist' ? normalizeWatchlist(presetFilters[key]) : normalizeSlug(presetFilters[key]);
                    });
                    numericFilterKeys.forEach(key => {
                        filters[key] = cleanNumber(presetFilters[key]);
                    });
                    this.filters = filters;
                    this.sortBy = normalizeSort(_.get(preset, 'sort.key'));
                    this.sortDesc = normalizeDirection(_.get(preset, 'sort.direction')) === 'desc';
                    this.presetName = preset.name;
                    this.applyFilters();
                },
                displayFilterValue: function (key, value) {
                    const optionSets = {
                        category: this.categoryOptions,
                        exchange: this.exchangeOptions,
                        ton_tag: this.tonTagOptions,
                        watchlist: this.watchlistFilterOptions
                    };
                    const option = _.find(optionSets[key] || [], ['value', value]);
                    return option ? option.text : _.startCase(_.toString(value));
                },
                currencyRoute: function (item) {
                    return {name: 'currency', params: {id: item.id}};
                },
                openCurrency: function (item) {
                    this.$router.push(this.currencyRoute(item));
                },
                sentimentColor: function (score) {
                    score = parseFloat(score);
                    if (!_.isFinite(score)) return 'grey';
                    if (score >= 35) return 'success';
                    if (score <= -35) return 'low';
                    return 'primary';
                },
                sentimentLabel: function (item) {
                    const score = parseInt(_.get(item, 'sentiment.score', item.sentiment_score), 10);
                    const prefix = _.isFinite(score) && score > 0 ? '+' : '';
                    return (_.get(item, 'sentiment.label') || 'score') + ' ' + prefix + (score || 0);
                },
                exchangeIcon: function (item) {
                    if (!this.filters.exchange) return 'mdi-minus';
                    return _.get(item, 'exchange_availability.matched') ? 'mdi-check-circle' : 'mdi-close-circle-outline';
                },
                exchangeIconColor: function (item) {
                    if (!this.filters.exchange) return 'grey';
                    return _.get(item, 'exchange_availability.matched') ? 'success' : 'grey';
                },
                exchangeLabel: function (item) {
                    if (!this.filters.exchange) return 'No exchange filter active';
                    return _.get(item, 'exchange_availability.matched') ? 'Available on selected exchange' : 'Not found on selected exchange';
                },
                tonStateColor: function (asset) {
                    const state = _.get(asset, 'verification_state');
                    if (state === 'verified') return 'success';
                    if (state === 'curated') return 'primary';
                    return 'warning';
                },
                tonStateIcon: function (asset) {
                    const state = _.get(asset, 'verification_state');
                    if (state === 'verified') return 'mdi-shield-check';
                    if (state === 'curated') return 'mdi-bookmark-check-outline';
                    return 'mdi-alert-circle-outline';
                },
                tonStateLabel: function (asset) {
                    return _.startCase(_.get(asset, 'verification_state', 'TON'));
                }
            }
        }
    });

})(window, _, axios, GeckoClient);

(function (window, _, axios, CoinGecko, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.ton;
    const options = GeckoClient.getOptions('ton');
    if (!route) return;

    const adminTokenStorageKey = 'TONBANKCARD:adminToken';
    const adminApiBaseUrl = '/api/admin';
    const tonCategoryOptions = ['native', 'stablecoin', 'jetton', 'defi', 'wallet', 'infrastructure', 'community'];
    const tonVerificationStateOptions = ['verified', 'curated', 'unverified'];
    const tonLinkTypeOptions = [
        {value: 'currency', text: 'Cryptocurrency page'},
        {value: 'project', text: 'Project catalog page'}
    ];
    const tonProjectCategoryOptions = ['defi', 'wallet', 'infrastructure', 'community', 'jetton', 'stablecoin', 'native'];

    const normalizeTag = value => {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    };

    const slugId = value => {
        return _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '-').replace(/^-+|-+$/g, '');
    };

    GeckoClient.router.addRoute({
        name: 'ton',
        path: route.path,
        component: {
            template: '#route-ton',
            data: function () {
                return {
                    tonAssets: [],
                    tonCategories: {},
                    tonLists: {},
                    tonMeta: null,
                    marketMeta: null,
                    tonMarketMap: {},
                    loadingCuration: false,
                    loadingTonMarkets: false,
                    curationError: '',
                    marketError: '',
                    tonFilters: {
                        tag: '',
                        category: '',
                        list: '',
                        state: ''
                    },
                    adminToken: localStorage.getItem(adminTokenStorageKey) || '',
                    adminActor: null,
                    adminLoading: false,
                    adminSaving: false,
                    adminNotice: '',
                    adminNoticeType: 'info',
                    editorOpen: false,
                    editorAsset: null,
                    editorIndex: -1,
                    editorIsNew: false,
                    tonCategoryOptions: tonCategoryOptions.slice(),
                    tonVerificationStateOptions: tonVerificationStateOptions.slice(),
                    tonLinkTypeOptions: tonLinkTypeOptions.slice(),
                    tonProjectCategoryOptions: tonProjectCategoryOptions.slice()
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.syncFiltersFromRoute();
                this.trackTonAchievement();
                this.fetchTonEcosystem();
                if (this.adminToken) this.refreshAdminSession();
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    this.fetchTonMarkets();
                },
                '$route.query': function () {
                    this.syncFiltersFromRoute();
                }
            },
            computed: {
                categoryList: function () {
                    return _.sortBy(_.map(this.tonCategories), 'title');
                },
                ecosystemLists: function () {
                    return _.sortBy(_.map(this.tonLists), 'title');
                },
                visibleTags: function () {
                    const hidden = ['ton_ecosystem', 'ton_asset'];
                    const tags = _.uniq(_.flatMap(this.tonAssets, asset => asset.tags || []))
                        .map(normalizeTag)
                        .filter(tag => tag && hidden.indexOf(tag) === -1);
                    return _.take(_.sortBy(tags), 12);
                },
                featuredAssets: function () {
                    return this.decorateAssets(this.tonAssets.filter(asset => asset.featured || _.includes(asset.list_ids || [], 'featured')));
                },
                filteredAssets: function () {
                    return this.decorateAssets(this.tonAssets.filter(asset => this.assetMatchesFilters(asset)));
                },
                hasActiveFilters: function () {
                    return !!(this.tonFilters.tag || this.tonFilters.category || this.tonFilters.list || this.tonFilters.state);
                },
                isAdminAuthenticated: function () {
                    return !!this.adminToken && !!_.get(this.adminActor, 'role');
                },
                canEditCuration: function () {
                    return this.isAdminAuthenticated && _.get(this.adminActor, 'permissions.write') === true;
                },
                verifiedCount: function () {
                    return this.tonAssets.filter(asset => asset.verification_state === 'verified').length;
                },
                unverifiedCount: function () {
                    return this.tonAssets.filter(asset => asset.verification_state === 'unverified').length;
                },
                curationUpdatedLabel: function () {
                    const updatedAt = _.get(this.tonMeta, 'ton_ecosystem.updated_at') || _.get(this.tonMeta, 'ton_ecosystem.index_built_at');
                    return updatedAt || (this.tonAssets.length ? 'Built-in defaults' : 'Pending');
                },
                tonInsightContext: function () {
                    if (!GeckoClient.ai || !this.tonAssets.length) return null;

                    return {
                        insight_type: 'ton_ecosystem_pulse',
                        subject: 'TON ecosystem pulse',
                        market_data_age_seconds: GeckoClient.ai.marketDataAgeSeconds(this.marketMeta || this.tonMeta),
                        market_data_updated_at: GeckoClient.ai.marketDataUpdatedAt(this.marketMeta || this.tonMeta),
                        market_data: {
                            vs_currency: this.$root.vsCurrencyId,
                            assets: this.filteredAssets.map(asset => {
                                const market = asset.market || {};
                                return {
                                    id: asset.id,
                                    coin_id: asset.coin_id || null,
                                    name: asset.name,
                                    symbol: asset.symbol,
                                    category: asset.category,
                                    verification_state: asset.verification_state,
                                    current_price: market.current_price || null,
                                    price_change_percentage_24h: market.price_change_percentage_24h_in_currency || null,
                                    market_cap: market.market_cap || null
                                };
                            }),
                            coverage: [
                                'Curated TON categories',
                                'Verified and unverified asset states',
                                'TON-tagged market filters'
                            ]
                        }
                    };
                },
                tonShareCard: function () {
                    const mover = _.first(this.filteredAssets.filter(asset => {
                        return _.isFinite(parseFloat(_.get(asset, 'market.price_change_percentage_24h_in_currency')));
                    }));

                    return {
                        title: 'TON ecosystem movers',
                        subtitle: this.filteredAssets.length + ' visible assets',
                        body: 'Curated TON assets with verification state, categories, and 24h market movement.',
                        route: _.get(this.$route, 'fullPath') || '/ton',
                        campaign: 'ton-movers',
                        context: 'ton_movers',
                        freshness: 'Updated ' + this.curationUpdatedLabel,
                        metrics: [
                            {label: 'Assets', value: String(this.tonAssets.length)},
                            {label: 'Verified', value: String(this.verifiedCount)},
                            {label: 'Visible', value: String(this.filteredAssets.length)},
                            {label: 'Top mover', value: mover ? _.toUpper(mover.symbol || mover.id) + ' ' + this.$root.changeFormat(_.get(mover, 'market.price_change_percentage_24h_in_currency')) : 'N/A'}
                        ]
                    };
                }
            },
            methods: {
                tonEndpoint: function () {
                    return options.apiBaseUrl || '/api/ton/assets';
                },
                syncFiltersFromRoute: function () {
                    const query = this.$route.query || {};
                    this.tonFilters = {
                        tag: normalizeTag(query.tag || ''),
                        category: normalizeTag(query.category || ''),
                        list: normalizeTag(query.list || ''),
                        state: normalizeTag(query.state || '')
                    };
                },
                fetchTonEcosystem: function () {
                    this.loadingCuration = true;
                    this.curationError = '';

                    return axios.get(this.tonEndpoint())
                        .then(response => {
                            const payload = response.data && response.data.ok === true ? response.data.data : response.data;
                            this.tonAssets = _.get(payload, 'assets', []);
                            this.tonCategories = _.get(payload, 'categories', {});
                            this.tonLists = _.get(payload, 'lists', {});
                            this.tonMeta = response.data && response.data.meta ? response.data.meta : null;
                            return this.fetchTonMarkets();
                        })
                        .catch(() => {
                            this.tonAssets = [];
                            this.tonCategories = {};
                            this.tonLists = {};
                            this.tonMeta = null;
                            this.curationError = 'TON curation feed unavailable';
                        })
                        .finally(() => this.loadingCuration = false);
                },
                fetchTonMarkets: function () {
                    const ids = _.uniq(this.tonAssets.map(asset => asset.coin_id).filter(Boolean));
                    if (!ids.length) {
                        this.tonMarketMap = {};
                        this.marketMeta = null;
                        return Promise.resolve([]);
                    }

                    const params = {
                        ids: ids.join(','),
                        per_page: Math.min(250, ids.length),
                        page: 1,
                        order: 'market_cap_desc',
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: '24h,7d,30d',
                        sparkline: false
                    };
                    const config = {params: params};

                    this.loadingTonMarkets = true;
                    this.marketError = '';

                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            this.tonMarketMap = _.keyBy(currencies || [], 'id');
                            this.marketMeta = CoinGecko.metaGet('coins/markets', config) || null;
                            return currencies;
                        })
                        .catch(() => {
                            this.tonMarketMap = {};
                            this.marketMeta = null;
                            this.marketError = 'TON market snapshot unavailable';
                            return [];
                        })
                        .finally(() => this.loadingTonMarkets = false);
                },
                decorateAssets: function (assets) {
                    return assets.map(asset => Object.assign({}, asset, {
                        market: this.marketForAsset(asset),
                        categoryLabel: this.categoryTitle(asset.category)
                    }));
                },
                marketForAsset: function (asset) {
                    if (!asset || !asset.coin_id) return null;
                    const direct = this.tonMarketMap[asset.coin_id];
                    if (direct) return direct;
                    const symbol = _.toLower(asset.symbol || '');
                    if (!symbol) return null;
                    return _.find(this.tonMarketMap, currency => _.toLower(currency.symbol) === symbol) || null;
                },
                assetMatchesFilters: function (asset) {
                    if (this.tonFilters.category && this.tonFilters.category !== asset.category) return false;
                    if (this.tonFilters.state && this.tonFilters.state !== asset.verification_state) return false;
                    if (this.tonFilters.list && !_.includes(asset.list_ids || [], this.tonFilters.list)) return false;

                    if (this.tonFilters.tag) {
                        const tags = (asset.tags || []).map(normalizeTag);
                        const lists = (asset.list_ids || []).map(normalizeTag);
                        if (
                            !_.includes(tags, this.tonFilters.tag)
                            && !_.includes(lists, this.tonFilters.tag)
                            && normalizeTag(asset.category) !== this.tonFilters.tag
                            && normalizeTag(asset.verification_state) !== this.tonFilters.tag
                        ) {
                            return false;
                        }
                    }

                    return true;
                },
                filterRoute: function (field, value) {
                    const query = Object.assign({}, this.$route.query || {});
                    value = normalizeTag(value || '');
                    if (value) query[field] = value;
                    else delete query[field];
                    return {name: 'ton', query: query};
                },
                clearFiltersRoute: function () {
                    return {name: 'ton'};
                },
                categoryTitle: function (id) {
                    return _.get(this.tonCategories, [id, 'title'], _.startCase(id || 'TON'));
                },
                listTitle: function (id) {
                    return _.get(this.tonLists, [id, 'title'], _.startCase(id || 'TON list'));
                },
                stateLabel: function (state) {
                    return _.startCase(state || 'unverified');
                },
                stateIcon: function (state) {
                    if (state === 'verified') return 'mdi-shield-check';
                    if (state === 'curated') return 'mdi-bookmark-check-outline';
                    return 'mdi-alert-circle-outline';
                },
                stateColor: function (state) {
                    if (state === 'verified') return 'success';
                    if (state === 'curated') return 'primary';
                    return 'warning';
                },
                assetRoute: function (asset) {
                    if (!asset) return {name: 'ton'};
                    if (asset.link_type === 'currency' && asset.coin_id) return {name: 'currency', params: {id: asset.coin_id}};
                    if (asset.link_type === 'project' && asset.id) return {name: 'ton-asset', params: {id: asset.id}};
                    if (asset.id) return {name: 'ton-asset', params: {id: asset.id}};
                    if (asset.route) return asset.route;
                    if (asset.coin_id) return {name: 'currency', params: {id: asset.coin_id}};
                    return {name: 'ton', query: {category: asset.category || ''}};
                },
                coinRoute: function (asset) {
                    if (asset && asset.coin_id) return {name: 'currency', params: {id: asset.coin_id}};
                    return null;
                },
                marketRoute: function (tag) {
                    return {name: 'markets', query: {tag: normalizeTag(tag)}};
                },
                shareTonMovers: function () {
                    if (!GeckoClient.share) return;
                    GeckoClient.share.share(this.tonShareCard);
                },
                trackTonAchievement: function () {
                    if (!GeckoClient.achievements) return;
                    GeckoClient.achievements.track('ton_viewed', {source_route: 'ton'});
                },
                adminClient: function () {
                    return axios.create({
                        baseURL: adminApiBaseUrl,
                        headers: this.adminToken ? {Authorization: 'Bearer ' + this.adminToken} : {}
                    });
                },
                refreshAdminSession: function () {
                    if (!this.adminToken) {
                        this.adminActor = null;
                        return Promise.resolve(null);
                    }
                    this.adminLoading = true;
                    return this.adminClient().get('/config')
                        .then(response => {
                            this.adminActor = _.get(response, 'data.data.actor') || null;
                            return this.adminActor;
                        })
                        .catch(error => {
                            if (_.get(error, 'response.status') === 401) {
                                localStorage.removeItem(adminTokenStorageKey);
                                this.adminToken = '';
                            }
                            this.adminActor = null;
                            return null;
                        })
                        .finally(() => this.adminLoading = false);
                },
                openEditor: function (asset, index) {
                    if (!this.canEditCuration) return;
                    const source = asset || {};
                    const link_type = source.link_type || (source.coin_id ? 'currency' : 'project');
                    this.editorAsset = {
                        id: source.id || '',
                        coin_id: source.coin_id || '',
                        name: source.name || '',
                        symbol: source.symbol || '',
                        category: source.category || 'jetton',
                        verification_state: source.verification_state || 'curated',
                        link_type: link_type,
                        project_category: source.project_category || (link_type === 'project' ? source.category || '' : ''),
                        description: source.description || '',
                        featured: !!source.featured,
                        tags: Array.isArray(source.tags) ? source.tags.slice() : ['ton_ecosystem'],
                        list_ids: Array.isArray(source.list_ids) ? source.list_ids.slice() : []
                    };
                    this.editorIndex = (typeof index === 'number') ? index : -1;
                    this.editorIsNew = !source.id;
                    this.editorOpen = true;
                },
                openCreator: function () {
                    if (!this.canEditCuration) return;
                    this.openEditor(null, -1);
                    this.editorIsNew = true;
                },
                closeEditor: function () {
                    this.editorOpen = false;
                    this.editorAsset = null;
                    this.editorIndex = -1;
                    this.editorIsNew = false;
                },
                fetchAdminContent: function () {
                    return this.adminClient().get('/config')
                        .then(response => {
                            const data = _.get(response, 'data.data') || {};
                            this.adminActor = data.actor || this.adminActor;
                            return _.get(data, 'content') || {ton_assets: []};
                        });
                },
                saveEditor: function () {
                    if (!this.canEditCuration || !this.editorAsset) return;
                    const draft = this.editorAsset;
                    if (!draft.id) draft.id = slugId(draft.name || draft.symbol);
                    if (!draft.id || !draft.name) {
                        this.adminNotice = 'Asset name and id are required.';
                        this.adminNoticeType = 'error';
                        return;
                    }
                    this.adminSaving = true;
                    this.adminNotice = '';
                    return this.fetchAdminContent()
                        .then(content => {
                            const assets = Array.isArray(content.ton_assets) ? content.ton_assets.slice() : [];
                            const matchIndex = _.findIndex(assets, entry => entry && entry.id === draft.id);
                            const link_type = draft.link_type === 'currency' ? 'currency' : 'project';
                            const payload = {
                                id: draft.id,
                                coin_id: draft.coin_id || '',
                                name: draft.name,
                                symbol: draft.symbol || '',
                                category: draft.category || 'jetton',
                                verification_state: draft.verification_state || 'curated',
                                link_type: link_type,
                                project_category: link_type === 'project' ? (draft.project_category || draft.category || '') : '',
                                description: draft.description || '',
                                featured: !!draft.featured,
                                tags: Array.isArray(draft.tags) ? draft.tags : [],
                                list_ids: Array.isArray(draft.list_ids) ? draft.list_ids : []
                            };
                            if (matchIndex >= 0) {
                                assets[matchIndex] = _.assign({}, assets[matchIndex], payload);
                            } else {
                                assets.push(payload);
                            }
                            return this.adminClient().put('/content', {
                                content: _.assign({}, content, {ton_assets: assets})
                            });
                        })
                        .then(() => {
                            this.adminNotice = 'TON asset saved.';
                            this.adminNoticeType = 'success';
                            this.closeEditor();
                            return this.fetchTonEcosystem();
                        })
                        .catch(error => {
                            this.adminNotice = _.get(error, 'response.data.error.message') || 'Saving the asset failed.';
                            this.adminNoticeType = 'error';
                        })
                        .finally(() => this.adminSaving = false);
                },
                deleteAsset: function (asset) {
                    if (!this.canEditCuration || !asset || !asset.id) return;
                    if (typeof window.confirm === 'function' && !window.confirm('Remove ' + (asset.name || asset.id) + ' from the TON catalog?')) return;
                    this.adminSaving = true;
                    this.adminNotice = '';
                    return this.fetchAdminContent()
                        .then(content => {
                            const assets = (Array.isArray(content.ton_assets) ? content.ton_assets : [])
                                .filter(entry => entry && entry.id !== asset.id);
                            const excluded = _.uniq((Array.isArray(content.ton_excluded_asset_ids) ? content.ton_excluded_asset_ids : []).concat([asset.id]));
                            return this.adminClient().put('/content', {
                                content: _.assign({}, content, {ton_assets: assets, ton_excluded_asset_ids: excluded})
                            });
                        })
                        .then(() => {
                            this.adminNotice = 'TON asset removed.';
                            this.adminNoticeType = 'success';
                            return this.fetchTonEcosystem();
                        })
                        .catch(error => {
                            this.adminNotice = _.get(error, 'response.data.error.message') || 'Removing the asset failed.';
                            this.adminNoticeType = 'error';
                        })
                        .finally(() => this.adminSaving = false);
                }
            }
        }
    });

})(window, _, axios, CoinGecko, GeckoClient);

(function (window, _, axios, CoinGecko, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig['ton-asset'];
    if (!route) return;

    const options = GeckoClient.getOptions('ton-asset', {
        title: 'TON Ecosystem Asset',
        apiBaseUrl: '/api/ton/assets'
    });
    const adminTokenStorageKey = 'TONBANKCARD:adminToken';
    const adminApiBaseUrl = '/api/admin';
    const tonCategoryOptions = ['native', 'stablecoin', 'jetton', 'defi', 'wallet', 'infrastructure', 'community'];
    const tonVerificationStateOptions = ['verified', 'curated', 'unverified'];
    const tonLinkTypeOptions = [
        {value: 'currency', text: 'Cryptocurrency page'},
        {value: 'project', text: 'Project catalog page'}
    ];
    const tonProjectCategoryOptions = ['defi', 'wallet', 'infrastructure', 'community', 'jetton', 'stablecoin', 'native'];

    const normalizeTag = value => {
        value = _.toString(value || '').toLowerCase().trim().replace(/[^a-z0-9._-]+/g, '_').replace(/^[._-]+|[._-]+$/g, '');
        return value === 'ton' ? 'ton_ecosystem' : value;
    };

    GeckoClient.router.addRoute({
        name: 'ton-asset',
        path: route.path,
        component: {
            template: '#route-ton-asset',
            data: function () {
                return {
                    asset: null,
                    tonCategoriesMap: {},
                    loadingCuration: false,
                    loadError: '',
                    adminToken: localStorage.getItem(adminTokenStorageKey) || '',
                    adminActor: null,
                    adminSaving: false,
                    adminNotice: '',
                    adminNoticeType: 'info',
                    editorOpen: false,
                    editorAsset: null,
                    tonCategoryOptions: tonCategoryOptions.slice(),
                    tonVerificationStateOptions: tonVerificationStateOptions.slice(),
                    tonLinkTypeOptions: tonLinkTypeOptions.slice(),
                    tonProjectCategoryOptions: tonProjectCategoryOptions.slice()
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title || 'TON Ecosystem Asset');
                this.fetchAsset();
                if (this.adminToken) this.refreshAdminSession();
            },
            watch: {
                '$route.params.id': function () {
                    this.fetchAsset();
                }
            },
            computed: {
                isAdminAuthenticated: function () {
                    return !!this.adminToken && !!_.get(this.adminActor, 'role');
                },
                canEditCuration: function () {
                    return this.isAdminAuthenticated && _.get(this.adminActor, 'permissions.write') === true;
                },
                tonAssetInsightContext: function () {
                    if (!this.asset || !GeckoClient.ai) return null;

                    const market = this.asset.market || null;
                    return {
                        insight_type: 'ton_ecosystem_pulse',
                        subject: (this.asset.name || this.asset.id) + ' TON asset',
                        market_data_age_seconds: GeckoClient.ai.marketDataAgeSeconds(market ? {last_updated_at: _.get(market, 'last_updated')} : null),
                        market_data_updated_at: GeckoClient.ai.marketDataUpdatedAt(market ? {last_updated_at: _.get(market, 'last_updated')} : null),
                        market_data: {
                            vs_currency: this.$root.vsCurrencyId,
                            assets: [{
                                id: this.asset.id,
                                coin_id: this.asset.coin_id || null,
                                name: this.asset.name,
                                symbol: this.asset.symbol,
                                category: this.asset.category,
                                verification_state: this.asset.verification_state,
                                description: this.asset.description || null,
                                current_price: market ? (market.current_price || null) : null,
                                price_change_percentage_24h: market ? (market.price_change_percentage_24h_in_currency || null) : null,
                                market_cap: market ? (market.market_cap || null) : null
                            }],
                            coverage: ['TON asset detail', 'Verification state', 'Category context']
                        }
                    };
                }
            },
            methods: {
                tonEndpoint: function () {
                    return options.apiBaseUrl || '/api/ton/assets';
                },
                assetId: function () {
                    return _.toString(_.get(this.$route, 'params.id') || '').toLowerCase();
                },
                fetchAsset: function () {
                    const id = this.assetId();
                    if (!id) {
                        this.asset = null;
                        return Promise.resolve(null);
                    }

                    this.loadingCuration = true;
                    this.loadError = '';

                    return axios.get(this.tonEndpoint())
                        .then(response => {
                            const payload = response.data && response.data.ok === true ? response.data.data : response.data;
                            this.tonCategoriesMap = _.get(payload, 'categories', {});
                            const assets = _.get(payload, 'assets', []);
                            const found = _.find(assets, asset => asset && asset.id === id);
                            this.asset = found || null;
                            if (found && found.coin_id) {
                                return this.fetchMarket(found);
                            }
                            return found;
                        })
                        .catch(() => {
                            this.asset = null;
                            this.loadError = 'TON asset details unavailable.';
                        })
                        .finally(() => this.loadingCuration = false);
                },
                fetchMarket: function (asset) {
                    if (!asset || !asset.coin_id) return Promise.resolve(asset);
                    return CoinGecko.coinsMarkets({
                        ids: asset.coin_id,
                        per_page: 1,
                        page: 1,
                        order: 'market_cap_desc',
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: '24h,7d,30d',
                        sparkline: false
                    }).then(currencies => {
                        const market = (currencies || [])[0] || null;
                        if (this.asset) this.asset = _.assign({}, this.asset, {market: market});
                        return this.asset;
                    }).catch(() => asset);
                },
                stateLabel: function (state) {
                    return _.startCase(state || 'unverified');
                },
                stateIcon: function (state) {
                    if (state === 'verified') return 'mdi-shield-check';
                    if (state === 'curated') return 'mdi-bookmark-check-outline';
                    return 'mdi-alert-circle-outline';
                },
                stateColor: function (state) {
                    if (state === 'verified') return 'success';
                    if (state === 'curated') return 'primary';
                    return 'warning';
                },
                categoryTitle: function (id) {
                    return _.get(this.tonCategoriesMap, [id, 'title'], _.startCase(id || 'TON'));
                },
                backRoute: function () {
                    return {name: 'ton'};
                },
                catalogRoute: function (field, value) {
                    const query = {};
                    value = normalizeTag(value || '');
                    if (value) query[field] = value;
                    return {name: 'ton', query: query};
                },
                categoryRoute: function (category) {
                    return {name: 'ton', query: {category: category || ''}};
                },
                coinRoute: function (asset) {
                    if (asset && asset.coin_id) return {name: 'currency', params: {id: asset.coin_id}};
                    return null;
                },
                adminClient: function () {
                    return axios.create({
                        baseURL: adminApiBaseUrl,
                        headers: this.adminToken ? {Authorization: 'Bearer ' + this.adminToken} : {}
                    });
                },
                refreshAdminSession: function () {
                    if (!this.adminToken) {
                        this.adminActor = null;
                        return Promise.resolve(null);
                    }
                    return this.adminClient().get('/config')
                        .then(response => {
                            this.adminActor = _.get(response, 'data.data.actor') || null;
                            return this.adminActor;
                        })
                        .catch(error => {
                            if (_.get(error, 'response.status') === 401) {
                                localStorage.removeItem(adminTokenStorageKey);
                                this.adminToken = '';
                            }
                            this.adminActor = null;
                            return null;
                        });
                },
                fetchAdminContent: function () {
                    return this.adminClient().get('/config')
                        .then(response => {
                            const data = _.get(response, 'data.data') || {};
                            this.adminActor = data.actor || this.adminActor;
                            return _.get(data, 'content') || {ton_assets: []};
                        });
                },
                openEditor: function () {
                    if (!this.canEditCuration || !this.asset) return;
                    const asset = this.asset;
                    const link_type = asset.link_type || (asset.coin_id ? 'currency' : 'project');
                    this.editorAsset = {
                        id: asset.id,
                        coin_id: asset.coin_id || '',
                        name: asset.name || '',
                        symbol: asset.symbol || '',
                        category: asset.category || 'jetton',
                        verification_state: asset.verification_state || 'curated',
                        link_type: link_type,
                        project_category: asset.project_category || (link_type === 'project' ? asset.category || '' : ''),
                        description: asset.description || '',
                        featured: !!asset.featured,
                        tags: Array.isArray(asset.tags) ? asset.tags.slice() : ['ton_ecosystem'],
                        list_ids: Array.isArray(asset.list_ids) ? asset.list_ids.slice() : []
                    };
                    this.editorOpen = true;
                },
                closeEditor: function () {
                    this.editorOpen = false;
                    this.editorAsset = null;
                },
                saveEditor: function () {
                    if (!this.canEditCuration || !this.editorAsset) return;
                    const draft = this.editorAsset;
                    if (!draft.id || !draft.name) {
                        this.adminNotice = 'Asset name and id are required.';
                        this.adminNoticeType = 'error';
                        return;
                    }
                    this.adminSaving = true;
                    this.adminNotice = '';
                    return this.fetchAdminContent()
                        .then(content => {
                            const assets = Array.isArray(content.ton_assets) ? content.ton_assets.slice() : [];
                            const matchIndex = _.findIndex(assets, entry => entry && entry.id === draft.id);
                            const link_type = draft.link_type === 'currency' ? 'currency' : 'project';
                            const payload = {
                                id: draft.id,
                                coin_id: draft.coin_id || '',
                                name: draft.name,
                                symbol: draft.symbol || '',
                                category: draft.category || 'jetton',
                                verification_state: draft.verification_state || 'curated',
                                link_type: link_type,
                                project_category: link_type === 'project' ? (draft.project_category || draft.category || '') : '',
                                description: draft.description || '',
                                featured: !!draft.featured,
                                tags: Array.isArray(draft.tags) ? draft.tags : [],
                                list_ids: Array.isArray(draft.list_ids) ? draft.list_ids : []
                            };
                            if (matchIndex >= 0) {
                                assets[matchIndex] = _.assign({}, assets[matchIndex], payload);
                            } else {
                                assets.push(payload);
                            }
                            return this.adminClient().put('/content', {
                                content: _.assign({}, content, {ton_assets: assets})
                            });
                        })
                        .then(() => {
                            this.adminNotice = 'TON asset saved.';
                            this.adminNoticeType = 'success';
                            this.closeEditor();
                            return this.fetchAsset();
                        })
                        .catch(error => {
                            this.adminNotice = _.get(error, 'response.data.error.message') || 'Saving the asset failed.';
                            this.adminNoticeType = 'error';
                        })
                        .finally(() => this.adminSaving = false);
                },
                deleteAsset: function () {
                    if (!this.canEditCuration || !this.asset) return;
                    const asset = this.asset;
                    if (typeof window.confirm === 'function' && !window.confirm('Remove ' + (asset.name || asset.id) + ' from the TON catalog?')) return;
                    this.adminSaving = true;
                    this.adminNotice = '';
                    return this.fetchAdminContent()
                        .then(content => {
                            const assets = (Array.isArray(content.ton_assets) ? content.ton_assets : [])
                                .filter(entry => entry && entry.id !== asset.id);
                            return this.adminClient().put('/content', {
                                content: _.assign({}, content, {ton_assets: assets})
                            });
                        })
                        .then(() => {
                            this.adminNotice = 'TON asset removed.';
                            this.adminNoticeType = 'success';
                            this.$router.push({name: 'ton'});
                        })
                        .catch(error => {
                            this.adminNotice = _.get(error, 'response.data.error.message') || 'Removing the asset failed.';
                            this.adminNoticeType = 'error';
                        })
                        .finally(() => this.adminSaving = false);
                }
            }
        }
    });

})(window, _, axios, CoinGecko, GeckoClient);

(function (window, GeckoClient) {
    'use strict';

    const routeConfig = GeckoClient.routesConfig.terms;
    if (!routeConfig) return;

    const setTitle = GeckoClient.setTitle;

    const termsOptions = GeckoClient.getOptions('terms');

    GeckoClient.router.addRoute({
        name: 'terms',
        path: routeConfig.path,
        component: {
            template: '#route-terms',
            created: function () {
                // update title meta tags
                setTitle(termsOptions.title)
            }
        }
    });

})(window, GeckoClient);

(function (window, _, GeckoClient) {
    'use strict';

    function walletProfileComponent(routeName, templateId) {
        const options = GeckoClient.getOptions(routeName);

        return {
            template: templateId,
            data: function () {
                const snapshot = GeckoClient.tonConnect ? GeckoClient.tonConnect.snapshot() : {};
                return {
                    connection: snapshot,
                    unsubscribeTonConnect: null,
                    watchlistSnapshot: {entries: []},
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.subscribeWallet();
                this.refreshWatchlistSnapshot();
                this.subscribeWatchlist();
            },
            beforeDestroy: function () {
                if (this.unsubscribeTonConnect) this.unsubscribeTonConnect();
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            computed: {
                featureEnabled: function () {
                    return !!_.get(this.connection, 'enabled');
                },
                wallet: function () {
                    return _.get(this.connection, 'wallet') || null;
                },
                isConnected: function () {
                    return _.get(this.connection, 'status') === 'connected' && !!this.wallet;
                },
                isConnecting: function () {
                    return _.get(this.connection, 'status') === 'connecting';
                },
                surfaceLabel: function () {
                    return GeckoClient.telegram && GeckoClient.telegram.active ? 'Telegram Mini App' : 'Browser';
                },
                statusLabel: function () {
                    if (!this.featureEnabled) return 'Disabled';
                    if (this.isConnecting) return 'Connecting';
                    if (this.isConnected) return 'Connected';
                    return 'Disconnected';
                },
                statusColor: function () {
                    if (!this.featureEnabled) return 'warning';
                    if (this.isConnected) return 'success';
                    if (this.isConnecting) return 'primary';
                    return 'grey';
                },
                statusIcon: function () {
                    if (!this.featureEnabled) return 'mdi-lock-alert-outline';
                    if (this.isConnected) return 'mdi-wallet-check-outline';
                    if (this.isConnecting) return 'mdi-progress-clock';
                    return 'mdi-wallet-outline';
                },
                connectionError: function () {
                    return _.get(this.connection, 'error') || '';
                },
                shortAddress: function () {
                    const address = _.get(this.wallet, 'address') || '';
                    if (address.length <= 18) return address;
                    return address.slice(0, 8) + '...' + address.slice(-6);
                },
                networkLabel: function () {
                    return _.startCase(_.get(this.wallet, 'network') || 'unknown');
                },
                walletName: function () {
                    return _.get(this.wallet, 'wallet_name') || _.get(this.wallet, 'app_name') || 'TON wallet';
                },
                walletMetaItems: function () {
                    if (!this.wallet) return [];

                    return [
                        {label: 'Wallet app', value: _.get(this.wallet, 'app_name') || this.walletName, icon: 'mdi-cellphone-link'},
                        {label: 'Network', value: this.networkLabel, icon: 'mdi-lan'},
                        {label: 'Platform', value: _.get(this.wallet, 'platform') || 'Unknown', icon: 'mdi-devices'},
                        {label: 'Provider', value: _.get(this.wallet, 'provider') || 'TON Connect', icon: 'mdi-connection'}
                    ];
                },
                supportedFeatures: function () {
                    return _.get(this.wallet, 'supported_features') || [];
                },
                watchlistCount: function () {
                    return (_.get(this.watchlistSnapshot, 'entries') || []).length;
                },
                watchlistStorageLabel: function () {
                    const mode = GeckoClient.watchlist ? GeckoClient.watchlist.storageMode : 'local';
                    if (mode === 'telegram_cloud') return 'Telegram CloudStorage';
                    if (mode === 'memory') return 'Memory';
                    return 'Local browser';
                },
                portfolioPlaceholders: function () {
                    return [
                        {label: 'Balances', icon: 'mdi-scale-balance', status: 'Read-only placeholder'},
                        {label: 'Jettons', icon: 'mdi-diamond-stone', status: 'Read-only placeholder'},
                        {label: 'Activity', icon: 'mdi-timeline-clock-outline', status: 'Read-only placeholder'}
                    ];
                },
                privacyRoute: function () {
                    return {name: 'privacy-policy'};
                }
            },
            methods: {
                subscribeWallet: function () {
                    const service = GeckoClient.tonConnect;
                    if (!service) return;

                    this.unsubscribeTonConnect = service.onChange(snapshot => {
                        this.connection = snapshot;
                    });
                    service.init();
                },
                subscribeWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(snapshot => {
                        this.watchlistSnapshot = snapshot || {entries: []};
                    });
                    watchlist.init().then(() => this.refreshWatchlistSnapshot());
                },
                refreshWatchlistSnapshot: function () {
                    this.watchlistSnapshot = GeckoClient.watchlist ? GeckoClient.watchlist.snapshot() : {entries: []};
                },
                connectWallet: function () {
                    if (!GeckoClient.tonConnect) return;
                    GeckoClient.telegram.hapticSelection();
                    GeckoClient.tonConnect.connect();
                },
                disconnectWallet: function () {
                    if (!GeckoClient.tonConnect) return;
                    GeckoClient.telegram.hapticSelection();
                    GeckoClient.tonConnect.disconnect();
                },
                copyAddress: function () {
                    const address = _.get(this.wallet, 'address');
                    if (!address || !_.get(navigator, 'clipboard.writeText')) return;
                    navigator.clipboard.writeText(address).then(() => {
                        this.$root.copiedModel = true;
                    }).catch(() => {});
                },
                featureLabel: function (feature) {
                    return _.startCase(String(feature || '').replace(/[_-]+/g, ' '));
                }
            }
        };
    }

    ['wallet-profile', 'settings'].forEach(routeName => {
        const route = GeckoClient.routesConfig[routeName];
        if (!route) return;

        GeckoClient.router.addRoute({
            name: routeName,
            path: route.path,
            component: walletProfileComponent(routeName, '#route-' + routeName)
        });
    });

})(window, _, GeckoClient);

(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.watchlist;
    const options = GeckoClient.getOptions('watchlist');
    if (!route) return;

    function dateValue(value) {
        const date = new Date(value || 0);
        return GeckoClient.utils.isValidDate(date) ? date.getTime() : 0;
    }

    GeckoClient.router.addRoute({
        name: 'watchlist',
        path: route.path,
        component: {
            template: '#route-watchlist',
            data: function () {
                return {
                    entries: [],
                    marketCurrencies: [],
                    loading: false,
                    marketError: false,
                    marketMeta: null,
                    marketConfig: null,
                    sortKey: 'added_at',
                    sortDirection: 'desc',
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title);
                this.initWatchlist();
            },
            beforeDestroy: function () {
                if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
            },
            watch: {
                '$root.vsCurrencyId': function () {
                    this.fetchWatchlistMarketData();
                }
            },
            computed: {
                sortOptions: function () {
                    return [
                        {text: 'Added', value: 'added_at'},
                        {text: 'Name', value: 'name'},
                        {text: 'Rank', value: 'market_cap_rank'},
                        {text: 'Price', value: 'current_price'},
                        {text: '24h change', value: 'price_change_percentage_24h_in_currency'},
                        {text: 'Market cap', value: 'market_cap'}
                    ];
                },
                entryIds: function () {
                    return this.entries.map(entry => entry.coin_id);
                },
                isEmpty: function () {
                    return this.entries.length === 0;
                },
                freshnessStatus: function () {
                    return _.get(this.marketMeta, 'freshness.cache_status', null);
                },
                isStale: function () {
                    return ['stale', 'expired', 'fallback'].indexOf(this.freshnessStatus) >= 0;
                },
                freshnessLabel: function () {
                    const status = this.freshnessStatus || 'fresh';
                    const label = ['pass', 'hit', 'fresh'].indexOf(status) >= 0 ? 'Fresh' : _.startCase(status);
                    const timestamp = _.get(this.marketMeta, 'freshness.last_updated_at')
                        || _.get(this.marketMeta, 'freshness.fetched_at');

                    return timestamp ? label + ' ' + this.relativeTime(timestamp) : label;
                },
                storageModeLabel: function () {
                    const mode = GeckoClient.watchlist ? GeckoClient.watchlist.storageMode : 'local';
                    if (mode === 'telegram_cloud') return 'Telegram CloudStorage';
                    if (mode === 'memory') return 'Memory fallback';
                    return 'Local';
                },
                sortedCurrencies: function () {
                    const direction = this.sortDirection === 'asc' ? 1 : -1;
                    const sortKey = this.sortKey;

                    return this.marketCurrencies.slice().sort((a, b) => {
                        const aValue = this.sortValue(a, sortKey);
                        const bValue = this.sortValue(b, sortKey);

                        if (_.isString(aValue) || _.isString(bValue)) {
                            return direction * String(aValue || '').localeCompare(String(bValue || ''));
                        }

                        return direction * ((parseFloat(aValue) || 0) - (parseFloat(bValue) || 0));
                    });
                },
                watchlistInsightContext: function () {
                    if (this.isEmpty || !this.marketCurrencies.length || !GeckoClient.ai) return null;

                    return {
                        insight_type: 'watchlist_digest',
                        subject: 'Watchlist digest',
                        market_data_age_seconds: GeckoClient.ai.marketDataAgeSeconds(this.marketMeta),
                        market_data_updated_at: GeckoClient.ai.marketDataUpdatedAt(this.marketMeta),
                        market_data: {
                            vs_currency: this.$root.vsCurrencyId,
                            storage_mode: this.storageModeLabel,
                            freshness_status: this.freshnessStatus || 'fresh',
                            sort_key: this.sortKey,
                            sort_direction: this.sortDirection,
                            assets: this.sortedCurrencies.slice(0, 20).map(currency => this.aiCurrencySnapshot(currency))
                        }
                    };
                },
                watchlistShareCard: function () {
                    const strongest = _.first(this.sortedCurrencies.filter(currency => _.isFinite(parseFloat(currency.price_change_percentage_24h_in_currency))));

                    return {
                        title: 'Watchlist snapshot',
                        subtitle: this.entries.length + ' saved assets',
                        body: this.isEmpty
                            ? 'Saved watchlist view on TONBANKCARD.'
                            : 'Prices, 24h moves, ranks, and saved assets in one watchlist snapshot.',
                        route: '/watchlist',
                        campaign: 'watchlist-snapshot',
                        context: 'watchlist_snapshot',
                        freshness: !this.isEmpty && this.marketMeta ? this.freshnessLabel : 'Saved watchlist state',
                        metrics: [
                            {label: 'Assets', value: String(this.entries.length)},
                            {label: 'Storage', value: this.storageModeLabel},
                            {label: 'Sort', value: _.startCase(this.sortKey) + ' ' + this.sortDirection},
                            {label: 'Top 24h', value: strongest ? this.ruleAssetLabel(strongest) + ' ' + this.changeLabel(strongest.price_change_percentage_24h_in_currency) : 'N/A'}
                        ]
                    };
                }
            },
            methods: {
                initWatchlist: function () {
                    const watchlist = GeckoClient.watchlist;
                    if (!watchlist) return;

                    this.watchlistUnsubscribe = watchlist.onChange(() => {
                        this.syncEntries();
                        this.fetchWatchlistMarketData();
                    });

                    watchlist.init().then(() => {
                        this.syncEntries();
                        this.fetchWatchlistMarketData();
                    });
                },
                syncEntries: function () {
                    const snapshot = GeckoClient.watchlist ? GeckoClient.watchlist.snapshot() : {entries: []};
                    this.entries = snapshot.entries || [];
                },
                fetchWatchlistMarketData: function () {
                    if (!this.entries.length) {
                        this.marketCurrencies = [];
                        this.marketMeta = null;
                        this.marketError = false;
                        return Promise.resolve([]);
                    }

                    const params = {
                        ids: this.entryIds.join(','),
                        per_page: Math.min(250, this.entryIds.length),
                        page: 1,
                        order: 'market_cap_desc',
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: '24h,7d,30d',
                        sparkline: true
                    };

                    this.marketConfig = {params: params};
                    this.loading = true;
                    this.marketError = false;

                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            const byId = _.keyBy(currencies || [], 'id');
                            this.marketCurrencies = this.entries.map(entry => {
                                return this.extendCurrency(byId[entry.coin_id] || this.fallbackCurrency(entry), entry);
                            });
                            this.marketMeta = CoinGecko.metaGet('coins/markets', this.marketConfig) || null;
                        })
                        .catch(() => {
                            this.marketError = true;
                            this.marketCurrencies = this.entries.map(entry => this.extendCurrency(this.fallbackCurrency(entry), entry));
                        })
                        .finally(() => this.loading = false);
                },
                extendCurrency: function (currency, entry) {
                    currency.route = {name: 'currency', params: {id: currency.id}};
                    currency.added_at = entry.added_at;
                    currency.watchlist_symbol = entry.symbol || currency.symbol;
                    return currency;
                },
                fallbackCurrency: function (entry) {
                    return {
                        id: entry.coin_id,
                        symbol: entry.symbol,
                        name: entry.name || _.startCase(entry.coin_id),
                        image: entry.image,
                        market_cap_rank: null,
                        current_price: null,
                        price_change_percentage_24h_in_currency: null,
                        market_cap: null,
                        total_volume: null,
                        sparkline_in_7d: {price: []}
                    };
                },
                aiCurrencySnapshot: function (currency) {
                    return GeckoClient.ai ? GeckoClient.ai.marketCurrencySnapshot(currency) : {};
                },
                removeFromWatchlist: function (currency) {
                    if (!GeckoClient.watchlist) return;

                    GeckoClient.watchlist.remove(currency, {sourceRoute: 'watchlist'})
                        .then(() => {
                            this.syncEntries();
                            this.fetchWatchlistMarketData();
                        });
                },
                toggleSortDirection: function () {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                },
                sortIcon: function () {
                    return this.sortDirection === 'asc' ? 'mdi-sort-ascending' : 'mdi-sort-descending';
                },
                sortValue: function (currency, key) {
                    if (key === 'added_at') return dateValue(currency.added_at);
                    if (key === 'name') return _.toLower(currency.name || currency.id);
                    return _.get(currency, key, 0);
                },
                priceLabel: function (value) {
                    return _.isFinite(parseFloat(value)) ? this.$root.priceFormat(value) : 'N/A';
                },
                marketCapLabel: function (value) {
                    return _.isFinite(parseFloat(value)) ? this.$root.marketCapFormat(value) : 'N/A';
                },
                changeLabel: function (value) {
                    return _.isFinite(parseFloat(value)) ? this.$root.changeFormat(value) : 'N/A';
                },
                ruleAssetLabel: function (currency) {
                    return _.toUpper(currency.watchlist_symbol || currency.symbol || currency.id || 'asset');
                },
                shareWatchlist: function () {
                    if (!GeckoClient.share) return;
                    GeckoClient.share.share(this.watchlistShareCard);
                },
                relativeTime: function (timestamp) {
                    const date = new Date(timestamp);
                    if (!GeckoClient.utils.isValidDate(date)) return '';

                    const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
                    if (seconds < 60) return 'now';
                    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
                    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
                    return Math.floor(seconds / 86400) + 'd ago';
                }
            }
        }
    });

})(window, _, CoinGecko, GeckoClient);

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

(function (window, navigator, _, Vue, Vuetify, GeckoClient, CoinGecko) {
    'use strict';

    const utils = GeckoClient.utils;
    const __ = GeckoClient.__;
    const preferences = GeckoClient.preferences;
    const formats = GeckoClient.formats;
    const router = GeckoClient.router;
    const currencyFormat = GeckoClient.currencyFormat;
    const isFiatCurrency = GeckoClient.isFiatCurrency;
    const getCurrencyFormatter = GeckoClient.getCurrencyFormatter;
    const vuetifyOptions = GeckoClient.getVuetifyOptions();
    const supportedVsCurrencies = GeckoClient.supportedVsCurrencies;
    const defaultVsCurrencyId = GeckoClient.defaultVsCurrencyId;
    const initialTheme = preferences.theme();
    const derivedDominanceAssets = {
        ton: {
            id: 'the-open-network',
            vsCurrencyId: 'usd'
        }
    };


    const vm = new Vue({
        el: '#app-wrapper',
        vuetify: new Vuetify(vuetifyOptions),
        router: router,
        data: function () {
            return {
                navigationDrawerModel: window.innerWidth >= 1903,
                supportedVsCurrencies: supportedVsCurrencies,
                defaultVsCurrencyId: defaultVsCurrencyId,
                vsCurrencyId: preferences.vsCurrency(),
                vsCurrencyNavDialogModel: false,
                vsCurrencyBarDialogModel: false,
                vsCurrency: {},
                theme: initialTheme,
                darkTheme: initialTheme === 'dark',
                isMobileUserAgent: utils.isMobileUserAgent(),
                hasDownloaded: false,
                global: null,
                derivedMarketCapPercentages: {},
                copiedModel: false,
                // keep "Intl" instances for performance (formats 50-100 times faster)
                priceFormatter: null,
                marketCapFormatter: null,
                volumeFormatter: null,
                volumeBTCFormatter: getCurrencyFormatter(formats.volume.locale, formats.volume.options, 'BTC', false),
                changeFormatter: Intl.NumberFormat(formats.change.locale, formats.change.options),
                dominanceFormatter: Intl.NumberFormat(formats.dominance.locale, formats.dominance.options),
                bigNumberFormatter: Intl.NumberFormat(formats.bigNumber.locale, formats.bigNumber.options),
                rateFormatter: Intl.NumberFormat(formats.rate.locale, formats.rate.options),
                ratioFormatter: Intl.NumberFormat(formats.ratio.locale, formats.ratio.options),
                spreadFormatter: Intl.NumberFormat(formats.spread.locale, formats.spread.options),
                scoreFormatter: Intl.NumberFormat(formats.score.locale, formats.score.options),
                basisFormatter: Intl.NumberFormat(formats.basis.locale, formats.basis.options),
                dateFormatter: Intl.DateTimeFormat(formats.date.locale, formats.date.options),
                chartYAxisValueFormatter: null,
                chartDateHourMinuteFormatter: Intl.DateTimeFormat(formats.chartDateHourMinute.locale, formats.chartDateHourMinute.options),
                chartDateMonthDayFormatter: Intl.DateTimeFormat(formats.chartDateMonthDay.locale, formats.chartDateMonthDay.options),
                chartDateYearMonthDayFormatter: Intl.DateTimeFormat(formats.chartDateYearMonthDay.locale, formats.chartDateYearMonthDay.options),
                chartTooltipDateFormatter: Intl.DateTimeFormat(formats.chartTooltipDate.locale, formats.chartTooltipDate.options),
                pwaInstallAvailable: GeckoClient.pwa.installAvailable
            };
        },
        watch: {
            vsCurrencyId: function (id) {
                // update vs currency and dependencies
                this.setVsCurrencyObject();
                preferences.vsCurrency(id);
            },
            darkTheme: function (dark) {
                // switch theme
                this.theme = dark ? 'dark' : 'light';
                this.$vuetify.theme.dark = dark;
                GeckoClient.setDocumentThemeClass(this.theme);
                this.applyTelegramVuetifyTheme();
                preferences.theme(this.theme);
            },
        },
        computed: {
            rtl: function () {
                return this.$vuetify.rtl;
            },
            totalMarketCap: function () {
                return _.get(this.global, ['total_market_cap', this.vsCurrencyId], null);
            },
            totalVolume24h: function () {
                return _.get(this.global, ['total_volume', this.vsCurrencyId], null);
            },
            totalCryptocurrencies: function () {
                return _.get(this.global, 'active_cryptocurrencies', null);
            },
            totalExchanges: function () {
                return _.get(this.global, 'markets', null);
            }
        },
        created: function () {
            // set initial vs currency
            this.setVsCurrencyObject();
            this.syncTelegramTheme();
            GeckoClient.telegram.onThemeChange(() => this.syncTelegramTheme());
            GeckoClient.pwa.onInstallChange(available => {
                this.pwaInstallAvailable = available;
            });
            // fetch global data for stats bar
            CoinGecko.global().then(global => {
                this.global = global;
                this.fetchDerivedMarketCapPercentages();
            }).catch(error => {
                console.warn('CoinGecko.global() failed', error);
            });
        },
        methods: {
            syncTelegramTheme: function () {
                if (!GeckoClient.telegram.active) {
                    GeckoClient.setDocumentThemeClass(this.theme);
                    return;
                }

                const theme = GeckoClient.telegram.colorScheme === 'dark' ? 'dark' : 'light';
                this.theme = theme;
                this.$vuetify.theme.dark = theme === 'dark';
                this.darkTheme = this.$vuetify.theme.dark;
                GeckoClient.setDocumentThemeClass(theme);
                this.applyTelegramVuetifyTheme();
            },
            applyTelegramVuetifyTheme: function () {
                if (!GeckoClient.telegram.active) return;

                const themeName = this.$vuetify.theme.dark ? 'dark' : 'light';
                const target = this.$vuetify.theme.themes[themeName] || {};
                const patch = GeckoClient.telegram.getVuetifyThemePatch();

                Object.keys(patch).forEach(key => {
                    this.$set(target, key, patch[key]);
                });

                const themeColor = patch.primary || patch.background;
                const metaThemeColor = document.querySelector('meta[name="theme-color"]');
                if (metaThemeColor && themeColor) {
                    metaThemeColor.content = themeColor;
                }
            },
            promptPwaInstall: function () {
                GeckoClient.pwa.promptInstall();
            },
            setVsCurrencyObject: function () {
                let defaultCurrency = null;

                for (let i = 0; i < this.supportedVsCurrencies.length; i++) {
                    const currency = this.supportedVsCurrencies[i];
                    if (currency.id === this.vsCurrencyId) {
                        // set cloned currency obj
                        this.vsCurrency = _.clone(currency);
                        defaultCurrency = null;
                        break;
                    } else if (currency.id === this.defaultVsCurrencyId) {
                        defaultCurrency = _.clone(currency);
                    }
                }
                // set cloned default currency obj
                if (defaultCurrency) this.vsCurrency = defaultCurrency;
                // type flags
                this.vsCurrency.isFiat = this.vsCurrency.type === 'fiat';
                this.vsCurrency.isCrypto = this.vsCurrency.type === 'crypto';
                this.vsCurrency.isCommodity = this.vsCurrency.type === 'commodity';

                // update vs currency dependent formatters
                this.updateCurrencyFormatters();
            },
            updateCurrencyFormatters: function () {
                // creates formatters based on current vs currency
                ['price', 'marketCap', 'volume', 'chartYAxisValue'].forEach(name => {
                    const format = formats[name];
                    this[name + 'Formatter'] = getCurrencyFormatter(format.locale, format.options, this.vsCurrency.id, this.vsCurrency.isFiat);
                });
            },
            marketCapPercentage: function (symbol) {
                symbol = _.toLower(_.trim(symbol));
                const upstreamPercentage = _.get(this.global, ['market_cap_percentage', symbol], null);

                return _.isFinite(parseFloat(upstreamPercentage))
                    ? upstreamPercentage
                    : _.get(this.derivedMarketCapPercentages, symbol, null);
            },
            fetchDerivedMarketCapPercentages: function () {
                _.forOwn(derivedDominanceAssets, (asset, symbol) => {
                    if (this.marketCapPercentage(symbol)) return;
                    this.fetchDerivedMarketCapPercentage(symbol, asset);
                });
            },
            fetchDerivedMarketCapPercentage: function (symbol, asset) {
                const vsCurrencyId = asset.vsCurrencyId || this.defaultVsCurrencyId || this.vsCurrencyId;
                const totalMarketCap = parseFloat(_.get(this.global, ['total_market_cap', vsCurrencyId], null));

                if (!_.isFinite(totalMarketCap) || totalMarketCap <= 0) {
                    return Promise.resolve(null);
                }

                return CoinGecko.coinsMarkets({
                    ids: asset.id,
                    vs_currency: vsCurrencyId,
                    per_page: 1,
                    page: 1,
                    sparkline: false
                }).then(currencies => {
                    const currency = _.find(currencies, ['id', asset.id]) || _.first(currencies) || {};
                    const marketCap = parseFloat(currency.market_cap);

                    if (_.isFinite(marketCap) && marketCap > 0) {
                        this.$set(this.derivedMarketCapPercentages, symbol, marketCap / totalMarketCap * 100);
                    }

                    return _.get(this.derivedMarketCapPercentages, symbol, null);
                }).catch(() => null);
            },
            priceFormat: function (price) {
                return currencyFormat(this.priceFormatter, price, this.vsCurrency.isFiat ? null : this.vsCurrency.unit);
            },
            priceTargetFormat: function (price, target, isFiat) {
                if (isFiat === undefined) isFiat = isFiatCurrency(target);
                const formatter = getCurrencyFormatter(formats.price.locale, formats.price.options, target, isFiat);
                return currencyFormat(formatter, price, isFiat ? null : target);
            },
            marketCapFormat: function (marketCap) {
                return currencyFormat(this.marketCapFormatter, marketCap, this.vsCurrency.isFiat ? null : this.vsCurrency.unit);
            },
            volumeFormat: function (volume) {
                return currencyFormat(this.volumeFormatter, volume, this.vsCurrency.isFiat ? null : this.vsCurrency.unit);
            },
            volumeBTCFormat: function (volume) {
                return currencyFormat(this.volumeBTCFormatter, volume, 'BTC');
            },
            volumeTargetFormat: function (volume, target, isFiat) {
                const formatter = getCurrencyFormatter(formats.volume.locale, formats.volume.options, target, isFiat);
                return currencyFormat(formatter, volume, target);
            },
            bigNumberFormat: function (number) {
                number = parseFloat(number);
                return _.isFinite(number) ? this.bigNumberFormatter.format(number) : null;
            },
            changeFormat: function (change) {
                change = parseFloat(change);
                return _.isFinite(change) ? this.changeFormatter.format(change / 100) : null;
            },
            dateFormatFromTimestamp: function (timestamp) {
                if (!timestamp) return null;

                const date = new Date(timestamp * 1000)
                return utils.isValidDate(date) ? this.dateFormatter.format(date) : null;
            },
            dateFormat: function (date) {
                date = new Date(date)
                return utils.isValidDate(date) ? this.dateFormatter.format(date) : null;
            },
            dominanceFormat: function (dominance) {
                dominance = parseFloat(dominance);
                return _.isFinite(dominance) ? this.dominanceFormatter.format(dominance / 100) : null;
            },
            rateFormat: function (rate) {
                rate = parseFloat(rate);
                return _.isFinite(rate) ? this.rateFormatter.format(rate / 100) : null;
            },
            ratioFormat: function (ratio) {
                ratio = parseFloat(ratio);
                return _.isFinite(ratio) ? this.ratioFormatter.format(ratio) : null;
            },
            spreadFormat: function (spread) {
                spread = parseFloat(spread);
                return _.isFinite(spread) ? this.spreadFormatter.format(spread / 100) : null;
            },
            basisFormat: function (basis) {
                basis = parseFloat(basis);
                return _.isFinite(basis) ? this.basisFormatter.format(basis / 100) : null;
            },
            scoreFormat: function (score) {
                score = parseFloat(score);
                return _.isFinite(score) ? this.scoreFormatter.format(score) : null;
            },
            chartXAxisDateFormat: function (timestamp, interval) {
                // for shortest possible format, it uses interval dependent formatters

                // show year, month and day for all-time data
                if (interval === 'max') return this.chartDateYearMonthDayFormatter.format(timestamp);

                interval = parseFloat(interval);
                if (_.isFinite(interval)) {
                    // show year, month and day for larger than 30 days
                    if (interval > 30) return this.chartDateYearMonthDayFormatter.format(timestamp);
                    // show month and day for 2-30 days
                    if (interval > 1) return this.chartDateMonthDayFormatter.format(timestamp);
                }
                // show hour and minute for 1-day or less
                return this.chartDateHourMinuteFormatter.format(timestamp);
            },
            chartYAxisValueFormat: function (value) {
                value = parseFloat(value);
                return _.isFinite(value) ? this.chartYAxisValueFormatter.format(value) : null;
            },
            chartTooltipDateFormat: function (date) {
                return this.chartTooltipDateFormatter.format(date);
            },
            changeIcon: function (change) {
                change = parseFloat(change);
                if (_.isFinite(change)) return change < 0 ? 'mdi-menu-down' : 'mdi-menu-up';
                return null;
            },
            changeColor: function (change) {
                change = parseFloat(change);
                if (_.isFinite(change)) return change < 0 ? 'low' : 'high';
                return null;
            },
            changeTextColor: function (change) {
                change = parseFloat(change);
                if (_.isFinite(change)) return change < 0 ? 'low_text' : 'high_text';
                return null;
            },
            changeColorClass: function (change) {
                change = parseFloat(change);
                if (_.isFinite(change)) return change < 0 ? 'low--text' : 'high--text';
                return null;
            },
            progressValue: function (value, high = 100, low = 0, ceil = false) {
                value = parseFloat(value);
                high  = parseFloat(high);
                low   = parseFloat(low);

                if (_.isFinite(value) && _.isFinite(high) && _.isFinite(low)) {
                    let percentage = Math.min(100, Math.max(0, 100 * (value - low) / (high - low)));
                    // returns 0-100 value
                    if (_.isFinite(percentage)) return ceil ? Math.ceil(percentage) : percentage;
                }

                return null;
            },
            coinGeckoTrustScoreColor: function (score) {
                // color name for trust score

                if (_.isString(score)) {
                    switch (score) {
                        case 'green': return 'high';
                        case 'yellow': return 'moderate';
                        case 'red': return 'low';
                    }
                }

                score = parseFloat(score)
                if (_.isFinite(score)) {
                    if (score > 6) return 'high';
                    if (score > 4) return 'moderate';
                    return 'low';
                }

                return null;
            },
            coinGeckoTrustScoreTextColor: function (score) {
                // text color name for trust score

                if (_.isString(score)) {
                    switch (score) {
                        case 'green': return 'high_text';
                        case 'yellow': return 'moderate_text';
                        case 'red': return 'low_text';
                    }
                }

                score = parseFloat(score)
                if (_.isFinite(score)) {
                    if (score > 6) return 'high_text';
                    if (score > 4) return 'moderate_text';
                    return 'low_text';
                }

                return null;
            },
            coinGeckoTrustScoreInteger: function (score) {
                // int value for trust score

                if (_.isString(score)) {
                    switch (score) {
                        case 'green': return 10;
                        case 'yellow': return 6;
                        case 'red': return 4;
                    }
                }

                score = parseFloat(score)
                return _.isFinite(score) ? Math.min(10, Math.max(0, Math.ceil(score))) : null;
            },
            coinGeckoTrustScoreText: function (score) {
                // text for trust score

                if (_.isString(score)) {
                    switch (score) {
                        case 'green': return __('High');
                        case 'yellow': return __('Moderate');
                        case 'red': return __('Low');
                        default: return __('N/A');
                    }
                }

                score = parseFloat(score)
                if (_.isFinite(score)) return (Math.min(10, Math.max(0, score))).toFixed(0);

                return __('N/A');
            },
            pairDisplay: function (base, target) {
                if (!_.isString(base) || !_.isString(target)) return '';

                // max of 10 char length for each part
                // truncates the excess and adds ellipsis
                let pair = base.length > 10 ? (base.substr(0, 7) + '...') : base;
                pair += '/';
                pair += target.length > 10 ? (target.substr(0, 7) + '...') : target;
                return pair;
            },
            hostFromUrl: function (url) {
                return utils.getHostFromURL(url, true);
            },
            pathFromUrl: function (url, removeSlash) {
                return utils.getPathFromURL(url, removeSlash);
            },
            copyToClipboard: function (text) {
                if (!navigator.clipboard) return;
                // copy text to clipboard and trigger "copied" snackbar
                navigator.clipboard.writeText(text).then(() => this.copiedModel = true);
            }
        },
    });

    vm.lodash = _;

})(window, navigator, _, Vue, Vuetify, GeckoClient, CoinGecko);

