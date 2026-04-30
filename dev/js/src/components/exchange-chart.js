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
