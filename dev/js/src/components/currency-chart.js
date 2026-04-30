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
