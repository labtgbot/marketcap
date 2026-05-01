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
