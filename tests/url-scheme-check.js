#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const root = path.resolve(__dirname, '..');
const logDir = path.join(root, 'test-logs');
const logPath = path.join(logDir, 'url-scheme-check.log');

fs.mkdirSync(logDir, {recursive: true});

const logLines = [];
const failures = [];

function log(message) {
    logLines.push(message);
}

function fail(message) {
    failures.push(message);
    log(`FAIL: ${message}`);
}

function readText(relativePath) {
    return fs.readFileSync(path.join(root, relativePath), 'utf8');
}

function getPath(source, key, defaultValue) {
    if (!source || !key) return defaultValue;

    const parts = String(key).split('.');
    let value = source;
    for (const part of parts) {
        if (value === null || value === undefined || !Object.prototype.hasOwnProperty.call(Object(value), part)) {
            return defaultValue;
        }
        value = value[part];
    }
    return value === undefined ? defaultValue : value;
}

function trimEnd(value, chars) {
    value = String(value === null || value === undefined ? '' : value);
    chars = chars || ' ';
    while (value.endsWith(chars)) {
        value = value.slice(0, -chars.length);
    }
    return value;
}

function createLodashStub() {
    return {
        cloneDeep: value => JSON.parse(JSON.stringify(value)),
        forOwn: (source, callback) => Object.keys(source || {}).forEach(key => callback(source[key], key)),
        get: getPath,
        isArray: Array.isArray,
        isDate: value => value instanceof Date,
        isFinite: Number.isFinite,
        isFunction: value => typeof value === 'function',
        isPlainObject: value => !!value && Object.prototype.toString.call(value) === '[object Object]',
        isString: value => typeof value === 'string' || value instanceof String,
        escape: value => String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;'),
        pick: (source, keys) => (keys || []).reduce((result, key) => {
            if (source && Object.prototype.hasOwnProperty.call(Object(source), key)) {
                result[key] = source[key];
            }
            return result;
        }, {}),
        replace: (value, pattern, replacement) => String(value === null || value === undefined ? '' : value).replace(pattern, replacement),
        startsWith: (value, prefix) => String(value || '').startsWith(prefix),
        toLower: value => String(value === null || value === undefined ? '' : value).toLowerCase(),
        toUpper: value => String(value === null || value === undefined ? '' : value).toUpperCase(),
        toString: value => String(value === null || value === undefined ? '' : value),
        trim: value => String(value === null || value === undefined ? '' : value).trim(),
        trimEnd: trimEnd
    };
}

function createContext() {
    const documentElement = {
        classList: {
            add: () => {},
            remove: () => {},
            toggle: () => {}
        },
        style: {
            setProperty: () => {}
        }
    };

    const location = {
        href: 'https://marketcap.example/premium',
        origin: 'https://marketcap.example',
        pathname: '/premium',
        protocol: 'https:'
    };

    const window = {
        CustomEvent: function CustomEvent(name, options) {
            this.name = name;
            this.detail = options && options.detail;
        },
        Telegram: {},
        addEventListener: () => {},
        alert: () => {},
        confirm: () => false,
        dispatchEvent: () => {},
        location: location
    };
    window.window = window;

    const navigator = {
        userAgent: 'node url-scheme-check'
    };

    const document = {
        createElement: tag => ({
            async: false,
            onerror: null,
            onload: null,
            src: '',
            tagName: String(tag || '').toUpperCase()
        }),
        documentElement: documentElement,
        head: {
            appendChild: () => {}
        },
        querySelector: () => null,
        querySelectorAll: () => [],
        title: 'TONBANKCARD'
    };

    const localStorage = {
        getItem: () => null,
        removeItem: () => {},
        setItem: () => {}
    };

    const axios = {
        interceptors: {
            request: {use: () => {}},
            response: {use: () => {}}
        },
        get: () => Promise.resolve({data: {data: {}}}),
        post: () => Promise.resolve({data: {data: {}}})
    };

    const GeckoClient = {
        links: {},
        options: {},
        premiumConfig: {},
        runtime: {profile: 'web'},
        supportedVsCurrencies: [],
        vuetifyOptions: {theme: {dark: false, themes: {dark: {}, light: {}}}},
        website: {title: 'TONBANKCARD', titleSeparator: ' - '}
    };

    const context = {
        CustomEvent: window.CustomEvent,
        Error: Error,
        GeckoClient: GeckoClient,
        Math: Math,
        Promise: Promise,
        URL: URL,
        Vue: {filter: () => {}},
        _: createLodashStub(),
        axios: axios,
        console: console,
        document: document,
        encodeURIComponent: encodeURIComponent,
        localStorage: localStorage,
        navigator: navigator,
        parseFloat: parseFloat,
        window: window
    };

    vm.createContext(context);
    vm.runInContext(readText('dev/js/src/initial.js'), context, {filename: 'dev/js/src/initial.js'});
    vm.runInContext(readText('dev/js/src/premium.js'), context, {filename: 'dev/js/src/premium.js'});

    return context;
}

function assertEqual(actual, expected, message) {
    if (actual !== expected) {
        fail(`${message}: expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`);
    }
}

function assertNull(actual, message) {
    if (actual !== null) {
        fail(`${message}: expected null, got ${JSON.stringify(actual)}`);
    }
}

function testValidURLString(context) {
    const validURLString = context.GeckoClient.utils.validURLString;
    log('Checking validURLString safe URL handling.');

    assertEqual(
        validURLString('https://example.com/path?x=1'),
        'https://example.com/path?x=1',
        'absolute HTTPS URL should pass'
    );
    assertEqual(
        validURLString('http://example.com/path'),
        'http://example.com/path',
        'absolute HTTP URL should pass'
    );
    assertEqual(
        validURLString('/markets?tab=ton', 'https://marketcap.example/currencies'),
        'https://marketcap.example/markets?tab=ton',
        'relative URL should resolve to an HTTP(S) URL when a base is supplied'
    );
    assertEqual(
        validURLString('tonbankcard', 'https://t.me/'),
        'https://t.me/tonbankcard',
        'provider handle should resolve against an HTTP(S) base'
    );

    [
        'javascript:alert(1)',
        '  JaVaScRiPt:alert(1)',
        'data:text/html,<script>alert(1)</script>',
        'vbscript:msgbox(1)',
        'ftp://example.com/wallet.txt'
    ].forEach(url => {
        assertNull(validURLString(url, 'https://marketcap.example/'), `dangerous or unsupported URL scheme should be rejected (${url})`);
    });
}

function setWebApp(context, methods) {
    context.GeckoClient.telegram.webApp = methods || null;
    context.window.Telegram.WebApp = methods || null;
}

function testPremiumInvoiceNavigation(context) {
    const openInvoice = context.GeckoClient.premium.openInvoice;
    const safeInvoice = 'https://t.me/$MarketCapBot?start=stars';
    const unsafeInvoices = [
        'javascript:alert(1)',
        'data:text/html,<script>alert(1)</script>'
    ];

    log('Checking premium invoice navigation scheme validation.');

    let invoiceCalls = [];
    setWebApp(context, {
        openInvoice: link => invoiceCalls.push(link)
    });
    openInvoice(safeInvoice);
    assertEqual(invoiceCalls.length, 1, 'safe invoice should be passed to Telegram openInvoice');
    assertEqual(invoiceCalls[0], safeInvoice, 'safe invoice should not be rewritten before openInvoice');

    unsafeInvoices.forEach(link => {
        invoiceCalls = [];
        setWebApp(context, {
            openInvoice: value => invoiceCalls.push(value)
        });
        openInvoice(link);
        assertEqual(invoiceCalls.length, 0, `unsafe invoice should not reach Telegram openInvoice (${link})`);
    });

    unsafeInvoices.forEach(link => {
        const telegramCalls = [];
        setWebApp(context, {
            openTelegramLink: value => telegramCalls.push(value)
        });
        openInvoice(link);
        assertEqual(telegramCalls.length, 0, `unsafe invoice should not reach Telegram openTelegramLink (${link})`);
    });

    unsafeInvoices.forEach(link => {
        const originalHref = 'https://marketcap.example/premium';
        setWebApp(context, null);
        context.window.location.href = originalHref;
        openInvoice(link);
        assertEqual(context.window.location.href, originalHref, `unsafe invoice should not update window.location.href (${link})`);
    });
}

try {
    log('URL scheme security check started.');
    const context = createContext();
    testValidURLString(context);
    testPremiumInvoiceNavigation(context);
} catch (err) {
    fail(err.stack || err.message);
}

fs.writeFileSync(logPath, `${logLines.join('\n')}\n`);

if (failures.length) {
    console.error(`URL scheme security check failed. See ${path.relative(root, logPath)} for details.`);
    process.exit(1);
}

console.log(`URL scheme security check passed. Log: ${path.relative(root, logPath)}`);
