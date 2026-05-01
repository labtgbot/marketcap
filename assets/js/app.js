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
        if (!GeckoClient.analytics) return;

        GeckoClient.analytics.emit(eventName, {
            coin_id: entry.coin_id,
            symbol: entry.symbol,
            source_route: _.get(options, 'sourceRoute') || _.get(options, 'source_route') || null,
            storage_mode: service.storageMode
        });
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

(function (window, _, axios, Vue, GeckoClient, CoinGecko) {
    'use strict';

    const __ = GeckoClient.__;
    const resultGroups = {
        coin: __( 'Currencies' ),
        ton_asset: __( 'TON assets' ),
        exchange: __( 'Exchanges' ),
        category: __( 'Categories' ),
        action: __( 'Actions' )
    };

    Vue.component('gc-search-bar', {
        template: '#component-search-bar',
        data: function () {
            return {
                model: null,
                search: null,
                loading: false,
                items: [],
                requestCounter: 0,
                searchOpened: false,
                watchlistIds: [],
                watchlistUnsubscribe: null
            };
        },
        created: function () {
            this.initWatchlist();
        },
        beforeDestroy: function () {
            if (this.watchlistUnsubscribe) this.watchlistUnsubscribe();
        },
        watch: {
            model: function (selected) {
                if (!selected || !selected.route || !selected.id) return;

                this.trackSearchSelection(selected);

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

                const resolved = _.isString(next) ? this.$router.resolve(next).route : this.$router.resolve(next).route;
                if (resolved.fullPath === this.$route.fullPath) return;
                this.$router.push(next).catch(() => {});
            }
        },
        methods: {
            avatarChar: function (name) {
                return _.toUpper(_.first(name)) || '?';
            },
            getQueryText: function () {
                return _.toLower(_.trim(this.search));
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
                return (this.isWatched(item) ? 'Remove ' : 'Add ') + item.name + ' ' + (this.isWatched(item) ? 'from' : 'to') + ' Watchlist';
            },
            toggleWatchlist: function (item) {
                if (!GeckoClient.watchlist) return;

                const entry = {
                    id: item.coin_id || item.id,
                    symbol: item.symbol,
                    name: item.name,
                    image: item.large || item.small || item.thumb
                };

                GeckoClient.watchlist.toggle(entry, {sourceRoute: 'search'})
                    .then(() => this.syncWatchlistIds());
            },
            setItems: function (results) {
                const items = [];
                const seenTypes = {};

                (results || []).forEach(item => {
                    const type = item.type || 'action';
                    if (!seenTypes[type]) {
                        seenTypes[type] = true;
                        items.push({header: resultGroups[type] || type});
                    }

                    items.push(item);
                });

                this.items = items;
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
                        this.setItems(_.get(data, 'results', []));
                    })
                    .catch(() => {
                        if (requestId === this.requestCounter) this.items = [];
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
                return this.fetchData(requestId);
            },
            trackSearchOpened: function () {
                if (!GeckoClient.analytics || this.searchOpened) return;

                this.searchOpened = true;
                GeckoClient.analytics.emit('search_opened', {
                    trigger: 'focus',
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
                this.trackSearchOpened();
                this.searchItems();
            }
        }
    });

})(window, _, axios, Vue, GeckoClient, CoinGecko);

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
        ton: 'toncoin',
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
            });
        }
    });

})(window, Vue, CoinGecko);
(function (window, VueRouter, GeckoClient) {
    'use strict';

    const router = GeckoClient.router = new VueRouter({
        mode: GeckoClient.routerMode,
        base: GeckoClient.routerBase,
        scrollBehavior: function () {
            document.getElementById('app').scrollIntoView();
        }
    });

    router.afterEach(() => GeckoClient.setCanonicalUrl())


})(window, VueRouter, GeckoClient);

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

(function (window, _, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;
    const options = GeckoClient.getOptions('currencies');
    const route = GeckoClient.routesConfig.currencies;
    const perPage = Math.min(100, options.perPage) || 50;

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
                    loadingGlobal: false,
                    loadingMarkets: false,
                    loadingTrending: false,
                    globalError: false,
                    marketError: false,
                    trendingError: false,
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
                tonCurrencies: function () {
                    const tonCoinIds = options.tonCoinIds || ['toncoin'];
                    return this.marketCurrencies.filter(currency => {
                        const id = _.toLower(currency.id);
                        const symbol = _.toLower(currency.symbol);
                        const name = _.toLower(currency.name);
                        return tonCoinIds.indexOf(id) >= 0 || symbol === 'ton' || name.indexOf('ton') >= 0;
                    }).slice(0, 4);
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
                }
            },
            methods: {
                fetchPulse: function () {
                    this.fetchGlobal();
                    this.fetchMarketCurrencies();
                    this.fetchTrendingCoins();
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
                }
            }
        }
    });

})(window, _, CoinGecko, GeckoClient);

(function (window, CoinGecko, GeckoClient) {
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

                    GeckoClient.watchlist.toggle(
                        {
                            id: currency.id,
                            symbol: currency.symbol,
                            name: currency.name,
                            image: _.get(currency, 'image.large') || _.get(currency, 'image.small') || _.get(currency, 'image.thumb')
                        },
                        {sourceRoute: 'coin_detail'}
                    ).then(() => this.syncWatchlistIds());
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

                    const marketCap = parseFloat(currency.marketCap);
                    const totalVolume = parseFloat(currency.totalVolume);
                    const volumePerMarketCap = totalVolume / marketCap;
                    currency.volumePerMarketCap = _.isFinite(volumePerMarketCap) ? volumePerMarketCap : null;

                    return currency;
                },
                vsConverted: function (priceObj) {
                    return _.get(priceObj, this.$root.vsCurrencyId, null);
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

})(window, CoinGecko, GeckoClient);

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

(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const marketsRoute = GeckoClient.routesConfig.markets;
    const marketsOptions = GeckoClient.getOptions('markets');
    const tableHeaders = marketsOptions.tableHeaders.filter(header => header.show);
    const perPage = Math.min(250, marketsOptions.perPage) || 100;

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
                    watchlistUnsubscribe: null
                };
            },
            created: function () {
                this.initWatchlist();
                this.fetchFirstCurrencies();
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
                }
            },
            computed: {
                tableHeaders: function () {
                    if (this.$vuetify.breakpoint.xs) {
                        // hide rank column in smartphones
                        return _.reject(tableHeaders, ['value', 'market_cap_rank']);
                    }
                    return tableHeaders;
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
                fetchCurrencies: function () {
                    const params = {
                        per_page: this.perPage,
                        page: ++this.page,
                        order: this.order,
                        vs_currency: this.$root.vsCurrencyId,
                        price_change_percentage: this.priceChanges.join(','),
                        sparkline: true
                    };
                    return CoinGecko.coinsMarkets(params)
                        .then(currencies => {
                            _.each(currencies, currency => {
                                currency.route = {name: 'currency', params: {id: currency.id}};
                                this.currencies.push(currency);
                            })
                            this.loadMore = currencies.length === this.perPage;
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
                    return this.fetchCurrencies().finally(() => this.loading = false);
                },
                fetchMoreCurrencies: function () {
                    this.loadMoreLoading = true;
                    return this.fetchCurrencies().finally(() => this.loadMoreLoading = false);
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

})(window, CoinGecko, GeckoClient);

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

    ['screener', 'support'].forEach(routeName => {
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

(function (window, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.ton;
    if (!route) return;

    GeckoClient.router.addRoute({
        name: 'ton',
        path: route.path,
        component: {
            template: '#route-ton',
            created: function () {
                GeckoClient.setTitle(GeckoClient.getOptions('ton').title);
            }
        }
    });

})(window, GeckoClient);

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
            id: 'toncoin',
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
                chartTooltipDateFormatter: Intl.DateTimeFormat(formats.chartTooltipDate.locale, formats.chartTooltipDate.options)
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
            // fetch global data for stats bar
            CoinGecko.global().then(global => {
                this.global = global;
                this.fetchDerivedMarketCapPercentages();
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

