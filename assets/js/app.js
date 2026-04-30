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
        if (typeof define === 'function' && define.amd) {
            // AMD. Register as an anonymous module.
            define(['exports', 'echarts'], factory);
        } else if (
            typeof exports === 'object' &&
            typeof exports.nodeName !== 'string'
        ) {
            // CommonJS
            factory(exports, require('echarts/lib/echarts'));
        } else {
            // Browser globals
            factory({}, root.echarts);
        }
    })(window, function(exports, echarts) {
        let log = function(msg) {
            if (typeof console !== 'undefined') {
                console && console.error && console.error(msg);
            }
        };
        if (!echarts) {
            log('ECharts is not Loaded');
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

(function (window, _, axios, GeckoClient) {
    'use strict';

    const __ = GeckoClient.__;
    const cg = GeckoClient.cg;
    const utils = GeckoClient.utils;
    const validURLString = utils.validURLString;
    const bitcointalkThreadUrl = utils.bitcointalkThreadUrl;
    const getCustomLink = GeckoClient.getCustomLink;

    function validateUrls(list) {
        return (list || []).map(url => validURLString(url)).filter(url => !!url);
    }

    const CoinGecko = window.CoinGecko = {
        baseUrl: 'https://api.coingecko.com/api/v3/',
        cacheMap: new Map(),
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
        cacheRegisterClearTimer: function () {
            this.cacheClearTimer = setInterval(() => this.cacheMap.clear(), this.cacheClearTimeout)
        },
        get: function (path, config, consistency, cache) {
            cache = cache || cg.cache;

            // immediately serve cached data
            if (cache && this.cacheHas(path, config)) return Promise.resolve(_.cloneDeep(this.cacheGet(path, config)));

            const client = axios.create({
                baseURL: this.baseUrl,
                timeout: cg.timeout > 0 ? cg.timeout : 0
            });

            return client.get(path, config)
                .then((res) => {
                    let data = res.data

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

(function (window, _, Vue, echarts, CoinGecko, GeckoClient) {
    'use strict';

    const formats = GeckoClient.formats;
    const utils = GeckoClient.utils;
    const __ = GeckoClient.__;

    const currencyChartOptions = GeckoClient.getOptions('currency-chart');

    Vue.component('gc-currency-chart', {
        props: ['currencyId'],
        template: '#component-currency-chart',
        data: function () {
            return {
                series: currencyChartOptions.series,
                selectedSeries: currencyChartOptions.defaultSeries,
                intervals: currencyChartOptions.intervals,
                selectedInterval: currencyChartOptions.defaultInterval,
                chart: null,
                cache: new Map(),
                loading: false
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
            },
            '$parent.inTransition': function (inTransition) {
                // fixes tab sliding issue
                if (!inTransition && this.$parent.isActive) this.$nextTick(() => this.resize());
            }
        },
        methods: {
            getCacheKey: function () {
                return [this.currencyId, this.$root.vsCurrencyId, this.selectedInterval].join('_');
            },
            fetchChartData: function (key) {
                const params = {
                    vs_currency: this.$root.vsCurrencyId,
                    days: this.selectedInterval
                };
                return CoinGecko.coinMarketChart(this.currencyId, params)
                    .then(data => {
                        data = {
                            date: data.market_caps.map(m => m[0]),
                            price: data.prices.map(p => p[1]),
                            marketCap: data.market_caps.map(m => m[1]),
                            volume: data.total_volumes.map(v => v[1]),
                        };

                        this.cache.set(key, data);

                        return data;
                    })
            },
            initChart: function (data) {
                const theme = this.$root.darkTheme ? 'dark' : undefined;

                const options = _.cloneDeep(currencyChartOptions.echartOptions);
                options.tooltip = options.tooltip || {};

                // adds chart data
                options.xAxis[0].data  = data.date;
                options.xAxis[1].data  = data.date;
                options.series[1].data = data.volume;

                // adds series options

                if (this.selectedSeries === 'price') {
                    options.series[0].name = __('Price');
                    options.series[0].id = 'price';
                    options.series[0].data = data.price;
                    options.tooltip.formatter = (params) => {
                        let html = '';
                        html += this.$root.chartTooltipDateFormat(params[0].axisValue);
                        html += '<br>';
                        html += _.map(params, p => p.marker + ' ' + p.seriesName + ': ' + this.$root.priceFormat(p.value)).join('<br>')
                        return html;
                    }
                } else {
                    options.series[0].name = __('Market Cap');
                    options.series[0].id = 'marketCap';
                    options.series[0].data = data.marketCap;
                    options.tooltip.formatter = (params) => {
                        let html = '';
                        html += this.$root.chartTooltipDateFormat(params[0].axisValue);
                        html += '<br>';
                        html += _.map(params, p => p.marker + ' ' + p.seriesName + ': ' + this.$root.marketCapFormat(p.value)).join('<br>')
                        return html;
                    }
                }

                // adds axis formatters

                options.yAxis[0].axisLabel = options.yAxis[0].axisLabel || {};
                options.yAxis[0].axisLabel.formatter = value => this.$root.chartYAxisValueFormat(value) + '\n\n';

                options.xAxis[0].axisLabel = options.xAxis[0].axisLabel || {};
                options.xAxis[0].axisLabel.formatter = value => this.$root.chartXAxisDateFormat(value, this.selectedInterval);

                options.xAxis[1].axisLabel = options.xAxis[1].axisLabel || {};
                options.xAxis[1].axisLabel.formatter = value => this.$root.chartXAxisDateFormat(value, this.selectedInterval);

                options.axisPointer.label.formatter = params => params.axisDimension === 'y' ? this.$root.chartYAxisValueFormat(params.value) : this.$root.chartXAxisDateFormat(params.value, this.selectedInterval);

                // wait for this.$refs.chartContainer to be available
                this.$nextTick(() => {
                    this.chart = echarts.init(this.$refs.chartContainer, theme);
                    this.chart.setOption(options);
                    this.chart.dispatchAction({
                        type: 'dataZoom',
                        start: 0,
                        end: 100
                    });
                })
            },
            updateChart: function () {
                if (this.chart) this.chart.dispose();

                // immediately serve cached data
                const key = this.getCacheKey();
                if (this.cache.has(key)) {
                    return this.initChart(this.cache.get(key))
                }

                // fetch remote data
                this.loading = true;
                this.fetchChartData(key)
                    .then(data => {
                        this.loading = false;
                        this.initChart(data)
                    })
                    .finally(() => this.loading = false);
            },
            resize() {
                if (this.chart) this.chart.resize()
            }
        }

    });



})(window, _, Vue, echarts, CoinGecko, GeckoClient);

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

(function (window, _, Vue, echarts, CoinGecko, GeckoClient) {
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
            initChart: function (data) {
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
                    this.chart = echarts.init(this.$refs.chartContainer, theme);
                    this.chart.setOption(options);
                });
            },
            updateChart: function () {
                if (this.chart) this.chart.dispose();

                // fetch remote data
                this.loading = true;
                this.fetchVolumeData(this.selectedInterval)
                    .then(data => {
                        this.loading = false;
                        this.initChart(data);
                    })
                    .finally(() => this.loading = false);
            },
            resize() {
                if (this.chart) this.chart.resize()
            }
        }

    });



})(window, _, Vue, echarts, CoinGecko, GeckoClient);

(function (window, Vue) {
    'use strict';

    Vue.component('gc-page-loader', {
        props: ['loading'],
        template: '#component-page-loader'
    });

})(window, Vue);

(function (window, _, axios, Vue, GeckoClient) {
    'use strict';

    const __ = GeckoClient.__;

    Vue.component('gc-search-bar', {
        template: '#component-search-bar',
        data: function () {
            return {
                model: null,
                search: null,
                loading: false,
                items: [],
                coins: null,
                exchanges: null
            };
        },
        watch: {
            model: function (selected) {
                if (!selected || !selected.route || !selected.id) return;
                const next = {
                    name: selected.route,
                    params: {id: selected.id}
                }
                const from = this.$route;
                if (next.name !== from.name || next.params.id !== from.params.id) {
                    this.$router.push(next);
                }
            }
        },
        methods: {
            avatarChar: function (name) {
                // result avatar char
                return _.toUpper(_.first(name)) || '?';
            },
            getQueryText: function () {
                // strips surrounding whitespaces and converts to lowercase
                return _.toLower(_.trim(this.search));
            },
            filterItem: function (item, queryText) {
                // look for queryText in currency name, symbol and id
                const itemString = _.toLower(item.name) + ' ' + _.toLower(item.symbol)  + ' ' + _.toLower(item.id);
                return _.includes(itemString, queryText);
            },
            filterList: function (list, queryText, size) {
                // filter list with 'filterItem' and restrict size
                const filtered = queryText === '' ? list : list.filter(item => this.filterItem(item, queryText));
                return _.slice(filtered, 0, size);
            },
            setItems: function () {
                const queryText = this.getQueryText();

                const categories = [
                    {
                        list: 'coins',
                        header: __( 'Currencies' ),
                        route: 'currency',
                        size: 10
                    },
                    {
                        list: 'exchanges',
                        header: __( 'Exchanges' ),
                        route: 'exchange',
                        size: 10
                    }
                ];

                const items = [];
                // get results separated by category
                _.each(categories, category => {
                    const listItems = this.filterList(this[category.list], queryText, category.size);
                    if (listItems.length) {
                        items.push({header: category.header});
                        listItems.forEach(item => {
                            item.route = category.route;
                            items.push(item);
                        });
                    }
                })
                this.items = items;
            },
            fetchData: function () {
                this.loading = true;

                // coingecko disabled CORS in search endpoint, so here is an alternative

                return axios.get('https://localstorage.one/crypto/data/search.json', {timeout: GeckoClient.cg.timeout})
                    .then(res => {
                        const search = res.data || {};

                        this.coins = _.map(search.coins, (coin,index) => {
                            return {
                                market_cap_rank: index + 1,
                                id: coin[0],
                                symbol: coin[1],
                                name: coin[2],
                                large: coin[3]
                            }
                        });

                        this.exchanges = _.map(search.exchanges, (exchange,index) => {
                            return {
                                id: exchange[0],
                                name: exchange[1],
                                large: exchange[2]
                            }
                        });

                        return search;
                    })
                    .finally(() => this.loading = false);

                /*
                return CoinGecko.search()
                    .then(search => {
                        this.coins = search.coins;
                        this.exchanges = search.exchanges;
                        return search;
                    })
                    .finally(() => this.loading = false);
                 */
            },
            searchItems: function () {
                // avoid multiple 'fetchData' calls
                if (this.loading) return;

                // search immediately
                if (this.coins && this.exchanges) this.setItems();

                // fetch before searching
                this.fetchData().then(() => this.setItems())
            },
            onFocus: function () {
                // forces download on bar click
                if (!this.coins || !this.exchanges) this.searchItems();
            }
        }
    });

})(window, _, axios, Vue, GeckoClient);

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
        usdc: 'usd-coin',
        usdt: 'tether',
        xrp: 'ripple'
    };

    const defaultSymbols = ['btc', 'eth'];


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

(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const currenciesOptions = GeckoClient.getOptions('currencies');
    const tableHeaders = currenciesOptions.tableHeaders.filter(header => header.show);
    const perPage = Math.min(250, currenciesOptions.perPage) || 100;

    GeckoClient.router.addRoute({
        name: 'currencies',
        path: GeckoClient.routesConfig.currencies.path,
        component: {
            template: '#route-currencies',
            data: function () {
                return {
                    order: currenciesOptions.order,
                    priceChanges: currenciesOptions.priceChanges,
                    currencies: [],
                    page: 0,
                    perPage: perPage,
                    loading: false,
                    loadMore: true,
                    loadMoreLoading: false,
                };
            },
            created: function () {
                this.fetchFirstCurrencies();
                // update title meta tags
                setTitle(currenciesOptions.title);
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
                toCurrency: function (currency) {
                    this.$router.push(currency.route);
                }
            }
        }
    });

})(window, CoinGecko, GeckoClient);

(function (window, CoinGecko, GeckoClient) {
    'use strict';

    const setTitle = GeckoClient.setTitle;

    const mainOptions = GeckoClient.getOptions('currency');

    const marketOptions = GeckoClient.getOptions('currency-market');

    const historicalOptions = GeckoClient.getOptions('currency-historical');
    // CoinGecko has auto granularity, min 120 day period to force 1-day interval
    const historicalPeriodDays  = Math.max(120, historicalOptions.periodDays) || 120;
    const historicalPeriodSecs  = historicalPeriodDays * 3600 * 24;
    const historicalToTimestamp = parseInt(new Date() / 1000);


    GeckoClient.router.addRoute({
        name: 'currency',
        path: GeckoClient.routesConfig.currency.path,
        component: {
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
                    historicalLoadMoreLoading: false
                };
            },
            created: function () {
                this.fetchCurrency()
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
        }
    });

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
                        per_page: this.per_page,
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
                theme: preferences.theme(),
                darkTheme: preferences.theme() === 'dark',
                isMobileUserAgent: utils.isMobileUserAgent(),
                hasDownloaded: false,
                global: null,
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
            // fetch global data for stats bar
            CoinGecko.global().then(global => this.global = global);
        },
        methods: {
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
                return _.get(this.global, ['market_cap_percentage', symbol], null);
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

