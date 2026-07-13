#!/usr/bin/env node
'use strict';

const fs = require('fs');
const vm = require('vm');

const components = {};
const lodash = {
    cloneDeep: value => JSON.parse(JSON.stringify(value)),
    get: () => null,
    isFinite: Number.isFinite,
    isString: value => typeof value === 'string',
    toUpper: value => String(value).toUpperCase(),
};
const GeckoClient = {
    formats: {converter: {locale: 'fr-FR', options: {maximumFractionDigits: 8}}},
};
const context = {
    window: {},
    _: lodash,
    Vue: {component: (name, definition) => { components[name] = definition; }},
    GeckoClient,
    Intl,
    Number,
    parseFloat,
    NaN,
};

// Evaluate only the currency helpers from initial.js to avoid booting the app.
const initial = fs.readFileSync('dev/js/src/initial.js', 'utf8');
const currencyStart = initial.indexOf('GeckoClient.getCurrencyFormatter');
const currencyEnd = initial.indexOf('// returns a custom link URL', currencyStart);
vm.runInNewContext(initial.slice(currencyStart, currencyEnd), context);

const formatter = Intl.NumberFormat('en-US', {style: 'currency', currency: 'USD'});
if (GeckoClient.currencyFormat(formatter, null) !== null || GeckoClient.currencyFormat(formatter, undefined) !== null) {
    throw new Error('currencyFormat must return null for missing values');
}

vm.runInNewContext(fs.readFileSync('dev/js/src/components/currency-converter.js', 'utf8'), context);
const converter = components['gc-currency-converter'];
const instance = {
    formatter: Intl.NumberFormat('fr-FR', {maximumFractionDigits: 8}),
};
instance.parseLocalizedNumber = converter.methods.parseLocalizedNumber.bind(instance);
if (instance.parseLocalizedNumber('0,5') !== 0.5 || instance.parseLocalizedNumber('1 234,5') !== 1234.5) {
    throw new Error('French locale decimal/group parsing failed');
}

console.log('Frontend formatting check passed.');
