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
