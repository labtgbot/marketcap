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
            parseLocalizedNumber: function (value) {
                if (_.isFinite(value)) return Number(value);
                if (!_.isString(value)) return NaN;
                const parts = this.formatter.formatToParts(12345.6);
                const group = (parts.find(part => part.type === 'group') || {}).value;
                const decimal = (parts.find(part => part.type === 'decimal') || {}).value || '.';
                let normalized = value.trim();
                if (group) normalized = normalized.split(group).join('');
                normalized = normalized.replace(/[\s\u00a0\u202f]/g, '');
                if (decimal !== '.') normalized = normalized.split(decimal).join('.');
                return Number(normalized);
            },
            baseUpdated: function () {
                const value = this.parseLocalizedNumber(this.baseModel) * this.rate;
                this.quoteModel = _.isFinite(value) ? this.formatter.format(value) : null;
            },
            quoteUpdated: function () {
                const value = this.parseLocalizedNumber(this.quoteModel) / this.rate;
                this.baseModel = _.isFinite(value) ? this.formatter.format(value) : null;
            }
        }
    });

})(window, _, Vue, GeckoClient);
