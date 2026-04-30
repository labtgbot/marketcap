#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const {spawn} = require('child_process');
const {chromium} = require('playwright');

const root = path.resolve(__dirname, '..');
const logDir = path.join(root, 'test-logs');
const smokeLogPath = path.join(logDir, 'browser-smoke.log');
const serverLogPath = path.join(logDir, 'php-server.log');
const host = process.env.SMOKE_HOST || '127.0.0.1';
const port = process.env.SMOKE_PORT || '8888';
const baseURL = process.env.SMOKE_BASE_URL || `http://${host}:${port}`;
const transparentPixel = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

fs.mkdirSync(logDir, {recursive: true});
fs.writeFileSync(smokeLogPath, '');
fs.writeFileSync(serverLogPath, '');

const smokeLog = fs.createWriteStream(smokeLogPath, {flags: 'a'});
const serverLog = fs.createWriteStream(serverLogPath, {flags: 'a'});

let server;

function log(message) {
    const line = `[${new Date().toISOString()}] ${message}`;
    smokeLog.write(`${line}\n`);
    console.log(message);
}

function fail(message) {
    throw new Error(message);
}

function assertEqual(actual, expected, label) {
    if (actual !== expected) {
        fail(`${label}: expected ${expected}, received ${actual === undefined ? 'undefined' : actual}`);
    }
}

function lastRequest(requests, label) {
    if (!requests.length) {
        fail(`${label}: expected a matching API request`);
    }

    return requests[requests.length - 1];
}

function requestRecord(url) {
    return {
        path: url.pathname.replace('/api/v3/', ''),
        params: Object.fromEntries(url.searchParams.entries()),
    };
}

function fulfillJson(route, data) {
    return route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(data),
    });
}

function nowSeries() {
    const now = Date.now();
    return [
        [now - 86400000, 60000],
        [now, 61000],
    ];
}

function marketChart() {
    const prices = nowSeries();
    return {
        prices,
        market_caps: prices.map(([timestamp, price]) => [timestamp, price * 19000000]),
        total_volumes: prices.map(([timestamp, price]) => [timestamp, price * 250000]),
    };
}

function marketCurrency(id, symbol, name, rank, price) {
    return {
        id,
        symbol,
        name,
        image: transparentPixel,
        market_cap_rank: rank,
        current_price: price,
        price_change_percentage_24h_in_currency: 1.25,
        price_change_percentage_7d_in_currency: 2.5,
        price_change_percentage_30d_in_currency: -0.5,
        market_cap: price * 19000000,
        total_volume: price * 250000,
        circulating_supply: 19000000,
        sparkline_in_7d: {price: [price - 2, price - 1, price]},
    };
}

function coinDetail(id, symbol, name, rank, price) {
    return {
        id,
        symbol,
        name,
        image: {large: transparentPixel},
        market_cap_rank: rank,
        categories: ['Cryptocurrency'],
        hashing_algorithm: 'SHA-256',
        genesis_date: '2009-01-03',
        block_time_in_minutes: 10,
        coingecko_score: 80,
        liquidity_score: 70,
        developer_score: 60,
        community_score: 50,
        platforms: {},
        links: {
            homepage: ['https://example.com/'],
            blockchain_site: [],
            announcement_url: [],
            official_forum_url: [],
            chat_url: [],
            subreddit_url: '',
            twitter_screen_name: '',
            facebook_username: '',
            bitcointalk_thread_identifier: null,
            repos_url: {github: [], bitbucket: []},
        },
        market_data: {
            current_price: {usd: price},
            price_change_percentage_24h_in_currency: {usd: 1.25},
            high_24h: {usd: price + 1000},
            low_24h: {usd: price - 1000},
            market_cap: {usd: price * 19000000},
            market_cap_change_24h_in_currency: {usd: 1000000},
            market_cap_change_percentage_24h_in_currency: {usd: 1.25},
            fully_diluted_valuation: {usd: price * 21000000},
            total_volume: {usd: price * 250000},
            circulating_supply: 19000000,
            total_supply: 21000000,
        },
    };
}

async function waitForServer() {
    const deadline = Date.now() + 15000;
    let lastError = null;

    while (Date.now() < deadline) {
        try {
            const response = await fetch(`${baseURL}/`);
            if (response.ok) {
                return;
            }
            lastError = new Error(`HTTP ${response.status}`);
        } catch (err) {
            lastError = err;
        }

        await new Promise(resolve => setTimeout(resolve, 250));
    }

    fail(`PHP server did not become ready at ${baseURL}: ${lastError ? lastError.message : 'unknown error'}`);
}

function startServer() {
    log(`Starting PHP server at ${baseURL}`);
    server = spawn('php', ['-S', `${host}:${port}`, 'dev/php/router.php'], {
        cwd: root,
        env: {
            ...process.env,
            TONBANKCARD_PROFILE: 'local',
            TONBANKCARD_BASE_URL: `${baseURL}/`,
            TONBANKCARD_LOCAL_BASE_URL: `${baseURL}/`,
            TONBANKCARD_CDN: 'false',
        },
        stdio: ['ignore', 'pipe', 'pipe'],
    });

    server.stdout.pipe(serverLog);
    server.stderr.pipe(serverLog);

    server.on('exit', (code, signal) => {
        log(`PHP server exited with code ${code === null ? 'null' : code} and signal ${signal === null ? 'null' : signal}`);
    });
}

async function stopServer() {
    if (!server || server.killed) {
        return;
    }

    server.kill('SIGTERM');
    await new Promise(resolve => {
        const timer = setTimeout(resolve, 2000);
        server.once('exit', () => {
            clearTimeout(timer);
            resolve();
        });
    });
}

async function installRoutes(context, requestLog) {
    await context.route('https://localstorage.one/crypto/data/search.json', route => fulfillJson(route, {
        coins: [
            ['bitcoin', 'btc', 'Bitcoin', transparentPixel],
            ['toncoin', 'ton', 'Toncoin', transparentPixel],
        ],
        exchanges: [
            ['binance', 'Binance', transparentPixel],
        ],
    }));

    await context.route('https://api.coingecko.com/api/v3/**', route => {
        const url = new URL(route.request().url());
        const apiPath = url.pathname.replace('/api/v3/', '');

        if (apiPath === 'global') {
            return fulfillJson(route, {
                data: {
                    active_cryptocurrencies: 12000,
                    markets: 750,
                    total_market_cap: {usd: 1234567890000},
                    total_volume: {usd: 98765432100},
                    market_cap_percentage: {btc: 51.1, eth: 17.2},
                },
            });
        }

        if (apiPath === 'search/trending') {
            return fulfillJson(route, {
                coins: [
                    {item: {id: 'bitcoin', name: 'Bitcoin', symbol: 'BTC', small: transparentPixel}},
                ],
                exchanges: [],
            });
        }

        if (apiPath === 'coins/markets') {
            requestLog.coinsMarkets.push(requestRecord(url));
            return fulfillJson(route, [
                marketCurrency('bitcoin', 'btc', 'Bitcoin', 1, 61000),
                marketCurrency('toncoin', 'ton', 'Toncoin', 12, 6.5),
            ]);
        }

        if (apiPath === 'coins/bitcoin') {
            requestLog.coinDetails.push(requestRecord(url));
            return fulfillJson(route, coinDetail('bitcoin', 'btc', 'Bitcoin', 1, 61000));
        }

        if (apiPath === 'coins/toncoin') {
            requestLog.coinDetails.push(requestRecord(url));
            return fulfillJson(route, coinDetail('toncoin', 'ton', 'Toncoin', 12, 6.5));
        }

        if (apiPath === 'coins/bitcoin/market_chart' || apiPath === 'coins/toncoin/market_chart') {
            requestLog.marketCharts.push(requestRecord(url));
            return fulfillJson(route, marketChart());
        }

        if (apiPath === 'exchanges') {
            requestLog.exchanges.push(requestRecord(url));
            return fulfillJson(route, [
                {
                    id: 'binance',
                    name: 'Binance',
                    image: transparentPixel,
                    trust_score: 10,
                    trust_score_rank: 1,
                    trade_volume_24h_btc: 100000,
                    trade_volume_24h_btc_normalized: 99000,
                    year_established: 2017,
                    country: 'Cayman Islands',
                },
            ]);
        }

        return fulfillJson(route, {});
    });
}

async function assertNoErrors(errors, label) {
    if (errors.length) {
        fail(`${label} produced browser errors:\n${errors.join('\n')}`);
    }
}

async function checkCurrenciesList(page, errors, requestLog) {
    log('Checking currencies list regression coverage');
    requestLog.coinsMarkets = [];

    await page.goto(`${baseURL}/`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currencies-table').waitFor({state: 'visible'});
    await page.locator('#currencies-table tbody tr', {hasText: 'Bitcoin'}).first().waitFor({state: 'visible'});
    await page.locator('#currencies-table tbody tr', {hasText: 'Toncoin'}).first().waitFor({state: 'visible'});

    const request = lastRequest(requestLog.coinsMarkets, 'currencies list request');
    assertEqual(request.params.vs_currency, 'usd', 'currencies list vs_currency');
    assertEqual(request.params.per_page, '100', 'currencies list per_page');
    assertEqual(request.params.page, '1', 'currencies list page');
    await assertNoErrors(errors, 'currencies list');
}

async function checkCoinDetail(page, errors, requestLog) {
    log('Checking coin detail, chart tab, and converter regression coverage');
    requestLog.coinDetails = [];
    requestLog.marketCharts = [];

    await page.goto(`${baseURL}/currency/bitcoin`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.getByText('Bitcoin Price', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByText('Rank #1', {exact: false}).first().waitFor({state: 'visible'});
    await page.locator('.gc-currency-chart-container canvas').first().waitFor({state: 'visible'});
    await page.locator('.gc-currency-converter').waitFor({state: 'visible'});
    await page.locator('.gc-currency-converter input').first().waitFor({state: 'visible'});
    await page.getByText('Buy', {exact: true}).first().waitFor({state: 'visible'});
    await page.getByText('Sell', {exact: true}).first().waitFor({state: 'visible'});

    const detailRequest = lastRequest(requestLog.coinDetails, 'coin detail request');
    assertEqual(detailRequest.path, 'coins/bitcoin', 'coin detail path');
    assertEqual(detailRequest.params.market_data, 'true', 'coin detail market_data');
    assertEqual(detailRequest.params.localization, 'false', 'coin detail localization');
    assertEqual(detailRequest.params.tickers, 'false', 'coin detail tickers');
    assertEqual(detailRequest.params.sparkline, 'false', 'coin detail sparkline');

    const chartRequest = lastRequest(requestLog.marketCharts, 'coin chart request');
    assertEqual(chartRequest.path, 'coins/bitcoin/market_chart', 'coin chart path');
    assertEqual(chartRequest.params.vs_currency, 'usd', 'coin chart vs_currency');
    assertEqual(chartRequest.params.days, '30', 'coin chart default interval');
    await assertNoErrors(errors, 'coin detail');
}

async function checkExchangesList(page, errors, requestLog) {
    log('Checking exchanges list regression coverage');
    requestLog.exchanges = [];

    await page.goto(`${baseURL}/exchanges`, {waitUntil: 'domcontentloaded'});
    await page.locator('#exchanges-table').waitFor({state: 'visible'});
    await page.getByText('Binance', {exact: false}).first().waitFor({state: 'visible'});

    const request = lastRequest(requestLog.exchanges, 'exchanges list request');
    assertEqual(request.params.per_page, '100', 'exchanges list per_page');
    assertEqual(request.params.page, '1', 'exchanges list page');
    await assertNoErrors(errors, 'exchanges list');
}

async function checkSearchInteraction(page, errors) {
    log('Checking search interaction');
    await page.goto(`${baseURL}/`, {waitUntil: 'domcontentloaded'});
    const search = page.locator('.gc-search-bar input[type="text"]');
    await search.waitFor({state: 'visible'});
    await search.fill('ton');
    await page.locator('.v-menu__content.menuable__content__active .v-list-item', {hasText: 'Toncoin'}).click();
    await page.waitForURL(`${baseURL}/currency/toncoin`);
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.getByText('Toncoin Price', {exact: false}).first().waitFor({state: 'visible'});
    await assertNoErrors(errors, 'search interaction');
}

async function run() {
    startServer();
    await waitForServer();

    const browser = await chromium.launch();
    const context = await browser.newContext();
    const requestLog = {
        coinsMarkets: [],
        coinDetails: [],
        marketCharts: [],
        exchanges: [],
    };
    await installRoutes(context, requestLog);

    const errors = [];
    const page = await context.newPage();

    page.on('console', message => {
        if (message.type() === 'error') {
            errors.push(`console error: ${message.text()}`);
        }
    });
    page.on('pageerror', error => {
        errors.push(`page error: ${error.message}`);
    });

    try {
        await checkCurrenciesList(page, errors, requestLog);
        await checkCoinDetail(page, errors, requestLog);
        await checkExchangesList(page, errors, requestLog);
        await checkSearchInteraction(page, errors);
    } catch (err) {
        const screenshotPath = path.join(logDir, 'browser-smoke-failure.png');
        await page.screenshot({path: screenshotPath, fullPage: true}).catch(() => {});
        log(`Failure screenshot: ${path.relative(root, screenshotPath)}`);
        throw err;
    } finally {
        await browser.close();
    }

    log(`Browser smoke passed. Logs: ${path.relative(root, smokeLogPath)}, ${path.relative(root, serverLogPath)}`);
}

run()
    .catch(err => {
        log(`Browser smoke failed: ${err.stack || err.message}`);
        process.exitCode = 1;
    })
    .finally(async () => {
        await stopServer();
        smokeLog.end();
        serverLog.end();
    });
