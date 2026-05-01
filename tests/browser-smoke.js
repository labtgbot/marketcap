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
        path: url.pathname.replace(/^\/api\/market\/?/, '').replace(/\/$/, ''),
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

function fulfillMarketJson(route, data) {
    return fulfillJson(route, {
        ok: true,
        data,
        meta: {
            request_id: 'browser-smoke',
            provider: {
                name: 'coingecko',
                plan: 'demo',
                attribution: {
                    name: 'CoinGecko',
                    url: 'https://www.coingecko.com/',
                },
                credentialed: false,
            },
            freshness: {
                fetched_at: new Date().toISOString(),
                last_updated_at: new Date().toISOString(),
                cache_age_seconds: 0,
                cache_status: 'pass',
            },
        },
    });
}

function fulfillSearchJson(route, data) {
    return fulfillJson(route, {
        ok: true,
        data,
        meta: {
            request_id: 'browser-smoke-search',
            search: {
                cache_status: 'pass',
                index_built_at: new Date().toISOString(),
                index_entry_count: data.results.length,
                query_length_bucket: '3-5',
                result_count: data.results.length,
                warnings: [],
            },
        },
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

function marketCurrency(id, symbol, name, rank, price, change = 1.25) {
    return {
        id,
        symbol,
        name,
        image: transparentPixel,
        market_cap_rank: rank,
        current_price: price,
        price_change_percentage_24h_in_currency: change,
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
    await context.route(`${baseURL}/api/search**`, route => {
        const url = new URL(route.request().url());
        const query = url.searchParams.get('q') || '';
        const normalizedQuery = query.toLowerCase();
        requestLog.searches.push({
            path: url.pathname,
            params: Object.fromEntries(url.searchParams.entries()),
        });

        const results = [];
        if (normalizedQuery) {
            const exchangeAsset = normalizedQuery.includes('usdt')
                ? {
                    id: 'exchange-tether-usd-ton',
                    title: 'Exchange Tether USD on TON',
                    coinId: 'tether',
                    asset: 'tether-usd-ton',
                }
                : {
                    id: 'exchange-toncoin',
                    title: 'Exchange Toncoin',
                    coinId: 'toncoin',
                    asset: 'toncoin',
                };

            results.push({
                searchId: `action:${exchangeAsset.id}`,
                type: 'action',
                id: exchangeAsset.id,
                title: exchangeAsset.title,
                name: exchangeAsset.title,
                subtitle: 'TON to USDT on TON',
                symbol: '',
                rank: 1,
                tags: ['exchange', 'changenow', 'ton_ecosystem'],
                contract_addresses: [],
                route: {
                    name: 'crypto-exchange',
                    query: {from: 'ton', to: 'usdtton', asset: exchangeAsset.asset},
                    path: `/crypto-exchange?from=ton&to=usdtton&asset=${exchangeAsset.asset}`,
                },
                links: {
                    web: `/crypto-exchange?from=ton&to=usdtton&asset=${exchangeAsset.asset}`,
                    telegram: `/app/exchange?from=ton&to=usdtton&asset=${exchangeAsset.asset}`,
                },
                analytics: {
                    event_name: 'search_result_selected',
                    result_type: 'action',
                    coin_id: exchangeAsset.coinId,
                    exchange_id: null,
                    rank: 1,
                    query_length_bucket: query.length ? '3-5' : 'empty',
                    surface: 'public_web',
                },
            });
        }

        results.push(
            {
                searchId: 'action:trending',
                type: 'action',
                id: 'trending',
                title: 'Trending coins',
                name: 'Trending coins',
                subtitle: 'Popular market searches',
                symbol: '',
                rank: results.length + 1,
                tags: ['trending', 'market'],
                contract_addresses: [],
                route: {
                    name: 'currencies',
                    query: {view: 'trending'},
                    path: '/?view=trending',
                },
                links: {
                    web: '/?view=trending',
                    telegram: '/app/search?view=trending',
                },
                analytics: {
                    event_name: 'search_result_selected',
                    result_type: 'action',
                    coin_id: null,
                    exchange_id: null,
                    rank: results.length + 1,
                    query_length_bucket: query.length ? '3-5' : 'empty',
                    surface: 'public_web',
                },
            },
            {
                searchId: 'coin:toncoin',
                type: 'coin',
                id: 'toncoin',
                coin_id: 'toncoin',
                title: 'Toncoin',
                name: 'Toncoin',
                subtitle: 'TON',
                symbol: 'TON',
                rank: results.length + 2,
                large: transparentPixel,
                tags: ['ton_ecosystem'],
                contract_addresses: [],
                route: {
                    name: 'currency',
                    params: {id: 'toncoin'},
                    path: '/currency/toncoin',
                },
                links: {
                    web: '/currency/toncoin',
                    telegram: '/app/coin/toncoin',
                },
                analytics: {
                    event_name: 'search_result_selected',
                    result_type: 'coin',
                    coin_id: 'toncoin',
                    exchange_id: null,
                    rank: results.length + 2,
                    query_length_bucket: '3-5',
                    surface: 'public_web',
                },
            },
            {
                searchId: 'ton_asset:tether-usd-ton',
                type: 'ton_asset',
                id: 'tether-usd-ton',
                coin_id: 'tether',
                title: 'Tether USD on TON',
                name: 'Tether USD on TON',
                subtitle: 'USDT on TON',
                symbol: 'USDT',
                rank: results.length + 3,
                tags: ['ton_ecosystem', 'ton_asset', 'stablecoin'],
                contract_addresses: ['EQCxE6mUtQJKFnGfaROTKOt1lZbDiiX1kCixRv7Nw2Id_sDs'],
                route: {
                    name: 'currency',
                    params: {id: 'tether'},
                    query: {network: 'ton'},
                    path: '/currency/tether?network=ton',
                },
                links: {
                    web: '/currency/tether?network=ton',
                    telegram: '/app/coin/tether?network=ton',
                },
                analytics: {
                    event_name: 'search_result_selected',
                    result_type: 'ton_asset',
                    coin_id: 'tether',
                    exchange_id: null,
                    rank: results.length + 3,
                    query_length_bucket: '3-5',
                    surface: 'public_web',
                },
            },
            {
                searchId: 'category:stablecoins',
                type: 'category',
                id: 'stablecoins',
                category_id: 'stablecoins',
                title: 'Stablecoins',
                name: 'Stablecoins',
                subtitle: 'Category',
                symbol: '',
                rank: results.length + 4,
                tags: ['category'],
                contract_addresses: [],
                route: {
                    name: 'currencies',
                    query: {category: 'stablecoins'},
                    path: '/?category=stablecoins',
                },
                links: {
                    web: '/?category=stablecoins',
                    telegram: '/app/search?category=stablecoins',
                },
                analytics: {
                    event_name: 'search_result_selected',
                    result_type: 'category',
                    coin_id: null,
                    exchange_id: null,
                    category_id: 'stablecoins',
                    rank: results.length + 4,
                    query_length_bucket: '3-5',
                    surface: 'public_web',
                },
            },
        );

        if (normalizedQuery === 'binance') {
            results.push({
                searchId: 'exchange:binance',
                type: 'exchange',
                id: 'binance',
                exchange_id: 'binance',
                title: 'Binance',
                name: 'Binance',
                subtitle: 'Exchange',
                symbol: '',
                rank: results.length + 1,
                tags: ['exchange'],
                contract_addresses: [],
                route: {
                    name: 'exchange',
                    params: {id: 'binance'},
                    path: '/exchange/binance',
                },
                links: {
                    web: '/exchange/binance',
                    telegram: '/app/search?type=exchange&id=binance',
                },
                analytics: {
                    event_name: 'search_result_selected',
                    result_type: 'exchange',
                    coin_id: null,
                    exchange_id: 'binance',
                    rank: results.length + 1,
                    query_length_bucket: '3-5',
                    surface: 'public_web',
                },
            });
        }

        return fulfillSearchJson(route, {
            query,
            normalized_query: query.toLowerCase(),
            surface: url.searchParams.get('surface') || 'public_web',
            result_count: results.length,
            results,
        });
    });

    await context.route('https://changenow.io/**', route => {
        if (route.request().url().endsWith('/stepper-connector.js')) {
            return route.fulfill({
                status: 200,
                contentType: 'application/javascript',
                body: 'window.__changenowStepperLoaded = true;',
            });
        }

        return route.fulfill({
            status: 200,
            contentType: 'text/html',
            body: '<!doctype html><title>ChangeNOW Widget</title><body>ChangeNOW Widget</body>',
        });
    });

    await context.route(`${baseURL}/api/market/**`, route => {
        const url = new URL(route.request().url());
        const apiPath = url.pathname.replace(/^\/api\/market\/?/, '').replace(/\/$/, '');

        if (apiPath === 'global') {
            requestLog.globals.push(requestRecord(url));
            return fulfillMarketJson(route, {
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
            requestLog.trending.push(requestRecord(url));
            return fulfillMarketJson(route, {
                coins: [
                    {item: {id: 'bitcoin', name: 'Bitcoin', symbol: 'BTC', small: transparentPixel}},
                ],
                exchanges: [],
            });
        }

        if (apiPath === 'search') {
            return fulfillMarketJson(route, {
                coins: [
                    {id: 'bitcoin', symbol: 'BTC', name: 'Bitcoin', large: transparentPixel, market_cap_rank: 1},
                    {id: 'toncoin', symbol: 'TON', name: 'Toncoin', large: transparentPixel, market_cap_rank: 12},
                ],
                exchanges: [
                    {id: 'binance', name: 'Binance', large: transparentPixel},
                ],
                categories: [],
                icos: [],
            });
        }

        if (apiPath === 'coins/markets') {
            if (url.searchParams.get('ids') === 'toncoin') {
                requestLog.tonDominanceMarkets.push(requestRecord(url));
                const toncoin = marketCurrency('toncoin', 'ton', 'Toncoin', 12, 6.5, -2.4);
                toncoin.market_cap = 7407407340;

                return fulfillMarketJson(route, [toncoin]);
            }

            requestLog.coinsMarkets.push(requestRecord(url));
            return fulfillMarketJson(route, [
                marketCurrency('bitcoin', 'btc', 'Bitcoin', 1, 61000, 1.25),
                marketCurrency('toncoin', 'ton', 'Toncoin', 12, 6.5, -2.4),
            ]);
        }

        if (apiPath === 'coins/bitcoin') {
            requestLog.coinDetails.push(requestRecord(url));
            return fulfillMarketJson(route, coinDetail('bitcoin', 'btc', 'Bitcoin', 1, 61000));
        }

        if (apiPath === 'coins/toncoin') {
            requestLog.coinDetails.push(requestRecord(url));
            return fulfillMarketJson(route, coinDetail('toncoin', 'ton', 'Toncoin', 12, 6.5));
        }

        if (apiPath === 'coins/bitcoin/market_chart' || apiPath === 'coins/toncoin/market_chart') {
            requestLog.marketCharts.push(requestRecord(url));
            return fulfillMarketJson(route, marketChart());
        }

        if (apiPath === 'exchanges') {
            requestLog.exchanges.push(requestRecord(url));
            return fulfillMarketJson(route, [
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

        return fulfillMarketJson(route, {});
    });
}

async function assertNoErrors(errors, label) {
    if (errors.length) {
        fail(`${label} produced browser errors:\n${errors.join('\n')}`);
    }
}

async function assertNoDirectProviderRequests(requests) {
    if (requests.length) {
        fail(`browser made direct provider data requests:\n${requests.join('\n')}`);
    }
}

async function checkMarketPulseHome(page, errors, requestLog) {
    log('Checking market pulse homepage');
    requestLog.globals = [];
    requestLog.trending = [];
    requestLog.coinsMarkets = [];
    requestLog.tonDominanceMarkets = [];

    await page.goto(`${baseURL}/`, {waitUntil: 'domcontentloaded'});
    await page.locator('#market-pulse').waitFor({state: 'visible'});
    await page.getByRole('heading', {name: /Market Pulse/i}).first().waitFor({state: 'visible'});
    await page.getByText('TON ecosystem pulse', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByText('Top gainers', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByText('Top losers', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByText('AI market summary', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByText('Fresh', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByRole('link', {name: /Full table/i}).waitFor({state: 'visible'});
    await page.getByRole('link', {name: 'Watchlist'}).first().waitFor({state: 'visible'});
    await page.getByRole('link', {name: 'TON view'}).first().waitFor({state: 'visible'});
    await page.locator('#market-pulse a', {hasText: 'Bitcoin'}).first().waitFor({state: 'visible'});
    await page.locator('.gc-stats-bar', {hasText: /ton:\s*0\.6%/i}).waitFor({state: 'visible'});

    const tonDominanceRequest = lastRequest(requestLog.tonDominanceMarkets, 'TON dominance coins request');
    assertEqual(tonDominanceRequest.params.ids, 'toncoin', 'TON dominance ids');
    assertEqual(tonDominanceRequest.params.vs_currency, 'usd', 'TON dominance vs_currency');

    const request = lastRequest(requestLog.coinsMarkets, 'market pulse coins request');
    assertEqual(request.params.vs_currency, 'usd', 'market pulse vs_currency');
    assertEqual(request.params.per_page, '50', 'market pulse per_page');
    assertEqual(request.params.page, '1', 'market pulse page');
    await assertNoErrors(errors, 'market pulse homepage');
}

async function checkMarketsList(page, errors, requestLog) {
    log('Checking markets table regression coverage');
    requestLog.coinsMarkets = [];

    await page.goto(`${baseURL}/markets`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currencies-table').waitFor({state: 'visible'});
    await page.locator('#currencies-table tbody tr', {hasText: 'Bitcoin'}).first().waitFor({state: 'visible'});
    await page.locator('#currencies-table tbody tr', {hasText: 'Toncoin'}).first().waitFor({state: 'visible'});

    const request = lastRequest(requestLog.coinsMarkets, 'markets table request');
    assertEqual(request.params.vs_currency, 'usd', 'markets table vs_currency');
    assertEqual(request.params.per_page, '100', 'markets table per_page');
    assertEqual(request.params.page, '1', 'markets table page');
    await assertNoErrors(errors, 'markets table');
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

async function checkSearchInteraction(page, errors, requestLog) {
    log('Checking search interaction');
    requestLog.searches = [];
    await page.goto(`${baseURL}/`, {waitUntil: 'domcontentloaded'});

    await page.keyboard.press('Control+K');
    const search = page.locator('.gc-search-bar-inline input[type="text"]');
    await search.waitFor({state: 'visible'});
    const focused = await search.evaluate(element => document.activeElement === element);
    if (!focused) {
        fail('Control+K did not focus the desktop smart search input');
    }

    const tonSearchResponse = page.waitForResponse(response => {
        try {
            const url = new URL(response.url());
            return url.pathname === '/api/search' && url.searchParams.get('q') === 'ton';
        } catch (err) {
            return false;
        }
    });
    await search.fill('ton');
    await tonSearchResponse;
    const activeMenu = page.locator('.v-menu__content.menuable__content__active').last();
    await activeMenu.getByText('Quick actions', {exact: true}).waitFor({state: 'visible'});
    await activeMenu.getByText('Coins', {exact: true}).waitFor({state: 'visible'});
    await activeMenu.getByText('TON assets', {exact: true}).waitFor({state: 'visible'});
    await activeMenu.getByText('Categories', {exact: true}).waitFor({state: 'visible'});
    await activeMenu.getByText('Exchange Toncoin', {exact: true}).waitFor({state: 'visible'});
    await activeMenu.getByText('Tether USD on TON', {exact: true}).waitFor({state: 'visible'});
    await activeMenu.getByText('Stablecoins', {exact: true}).waitFor({state: 'visible'});
    if (await activeMenu.getByText('Binance', {exact: true}).count()) {
        fail('TON search exposed a third-party venue result instead of the first-party exchange action');
    }
    await activeMenu.locator('.v-list-item').filter({hasText: /^\s*Toncoin\s+TON/}).first().click();
    await page.waitForURL(`${baseURL}/currency/toncoin`);
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.getByText('Toncoin Price', {exact: false}).first().waitFor({state: 'visible'});
    const request = requestLog.searches.find(entry => entry.params.q === 'ton') || lastRequest(requestLog.searches, 'smart search request');
    assertEqual(request.path, '/api/search', 'smart search path');
    assertEqual(request.params.q, 'ton', 'smart search query');

    await page.goto(`${baseURL}/`, {waitUntil: 'domcontentloaded'});
    await page.keyboard.press('Control+K');
    await search.waitFor({state: 'visible'});
    await search.fill('');
    const recentMenu = page.locator('.v-menu__content.menuable__content__active').last();
    await recentMenu.getByText('Recent searches', {exact: true}).waitFor({state: 'visible'});
    await recentMenu.locator('.v-list-item', {hasText: 'Toncoin'}).first().waitFor({state: 'visible'});

    const exchangeSearchResponse = page.waitForResponse(response => {
        try {
            const url = new URL(response.url());
            return url.pathname === '/api/search' && url.searchParams.get('q') === 'ton';
        } catch (err) {
            return false;
        }
    });
    await search.fill('ton');
    await exchangeSearchResponse;
    const exchangeMenu = page.locator('.v-menu__content.menuable__content__active').last();
    await exchangeMenu.locator('.v-list-item', {hasText: 'Exchange Toncoin'}).first().click();
    await page.waitForURL(`${baseURL}/crypto-exchange?from=ton&to=usdtton&asset=toncoin`);
    await page.locator('#crypto-exchange').waitFor({state: 'visible'});
    await page.getByRole('heading', {name: /Crypto Exchange/i}).first().waitFor({state: 'visible'});
    const widget = page.locator('#iframe-widget').first();
    await widget.waitFor({state: 'visible'});
    const widgetSrc = await widget.getAttribute('src');
    if (!widgetSrc || !widgetSrc.includes('from=ton') || !widgetSrc.includes('to=usdtton')) {
        fail(`crypto exchange widget did not receive the searched TON pair: ${widgetSrc || 'missing src'}`);
    }
    await assertNoErrors(errors, 'search interaction');
}

async function checkSearchMobileDialog(page, errors, requestLog) {
    log('Checking compact mobile search dialog');
    requestLog.searches = [];
    await page.setViewportSize({width: 360, height: 760});
    await page.goto(`${baseURL}/`, {waitUntil: 'domcontentloaded'});

    const trigger = page.getByRole('button', {name: /Open search/i});
    await trigger.waitFor({state: 'visible'});
    await trigger.click();

    const dialog = page.locator('.gc-search-dialog-card').first();
    await dialog.waitFor({state: 'visible'});
    const search = page.locator('.gc-search-dialog-field input[type="text"]').first();
    await search.waitFor({state: 'visible'});
    const usdtSearchResponse = page.waitForResponse(response => {
        try {
            const url = new URL(response.url());
            return url.pathname === '/api/search' && url.searchParams.get('q') === 'usdt';
        } catch (err) {
            return false;
        }
    });
    await search.click();
    await page.keyboard.type('usdt');
    await usdtSearchResponse;
    await page.locator('.v-menu__content.menuable__content__active', {hasText: 'Tether USD on TON'}).waitFor({state: 'visible'});

    const layout = await page.evaluate(() => ({
        viewportWidth: window.innerWidth,
        scrollWidth: Math.max(document.documentElement.scrollWidth, document.body.scrollWidth),
        dialogBottom: Math.round(document.querySelector('.gc-search-dialog-card').getBoundingClientRect().bottom),
        viewportHeight: window.innerHeight,
    }));

    if (layout.scrollWidth > layout.viewportWidth) {
        fail(`mobile search dialog overflowed horizontally: ${JSON.stringify(layout)}`);
    }
    if (layout.dialogBottom < layout.viewportHeight - 1) {
        fail(`mobile search dialog did not cover the compact search surface: ${JSON.stringify(layout)}`);
    }

    await assertNoErrors(errors, 'compact mobile search dialog');
}

async function checkWatchlistPersistence(page, errors) {
    log('Checking watchlist add, remove, and reload persistence');

    await page.goto(`${baseURL}/markets`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currencies-table').waitFor({state: 'visible'});
    await page.getByRole('button', {name: /Add Bitcoin to Watchlist/i}).first().click();
    await page.getByRole('button', {name: /Remove Bitcoin from Watchlist/i}).first().waitFor({state: 'visible'});

    const storedAfterAdd = await page.evaluate(() => {
        const raw = window.localStorage.getItem('TONBANKCARD:watchlist:v1');
        return raw ? JSON.parse(raw) : null;
    });
    if (!storedAfterAdd || !storedAfterAdd.entries || !storedAfterAdd.entries.some(entry => entry.coin_id === 'bitcoin')) {
        fail(`Bitcoin was not stored after add: ${JSON.stringify(storedAfterAdd)}`);
    }

    await page.reload({waitUntil: 'domcontentloaded'});
    await page.locator('#currencies-table').waitFor({state: 'visible'});
    await page.getByRole('button', {name: /Remove Bitcoin from Watchlist/i}).first().waitFor({state: 'visible'});

    await page.goto(`${baseURL}/watchlist`, {waitUntil: 'domcontentloaded'});
    await page.locator('#watchlist').waitFor({state: 'visible'});
    await page.getByText('Bitcoin', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByRole('button', {name: /Remove Bitcoin from Watchlist/i}).first().click();
    await page.getByText('No watched coins yet', {exact: false}).first().waitFor({state: 'visible'});

    const storedAfterRemove = await page.evaluate(() => {
        const raw = window.localStorage.getItem('TONBANKCARD:watchlist:v1');
        return raw ? JSON.parse(raw) : null;
    });
    if (storedAfterRemove && storedAfterRemove.entries && storedAfterRemove.entries.some(entry => entry.coin_id === 'bitcoin')) {
        fail(`Bitcoin remained in storage after remove: ${JSON.stringify(storedAfterRemove)}`);
    }

    await assertNoErrors(errors, 'watchlist persistence');
}

async function checkWatchlistUnavailableStorageFallback(browser) {
    log('Checking watchlist unavailable-storage fallback');

    const context = await browser.newContext();
    const requestLog = {
        globals: [],
        coinsMarkets: [],
        coinDetails: [],
        marketCharts: [],
        exchanges: [],
        trending: [],
        tonDominanceMarkets: [],
        searches: [],
    };
    await installRoutes(context, requestLog);
    await context.addInitScript(() => {
        const originalGetItem = Storage.prototype.getItem;
        const originalSetItem = Storage.prototype.setItem;
        const originalRemoveItem = Storage.prototype.removeItem;
        const blocksWatchlist = key => String(key || '').toLowerCase().includes('watchlist');

        Storage.prototype.getItem = function (key) {
            if (blocksWatchlist(key)) throw new Error('watchlist storage unavailable');
            return originalGetItem.call(this, key);
        };
        Storage.prototype.setItem = function (key, value) {
            if (blocksWatchlist(key)) throw new Error('watchlist storage unavailable');
            return originalSetItem.call(this, key, value);
        };
        Storage.prototype.removeItem = function (key) {
            if (blocksWatchlist(key)) throw new Error('watchlist storage unavailable');
            return originalRemoveItem.call(this, key);
        };
    });

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
        await page.goto(`${baseURL}/markets`, {waitUntil: 'domcontentloaded'});
        await page.locator('#currencies-table').waitFor({state: 'visible'});
        await page.getByRole('button', {name: /Add Bitcoin to Watchlist/i}).first().click();
        await page.goto(`${baseURL}/watchlist`, {waitUntil: 'domcontentloaded'});
        await page.locator('#watchlist').waitFor({state: 'visible'});
        await page.getByText('Bitcoin', {exact: false}).first().waitFor({state: 'visible'});

        const storageMode = await page.evaluate(() => window.GeckoClient.watchlist.storageMode);
        if (storageMode !== 'memory') {
            fail(`Expected memory watchlist fallback, got ${storageMode}`);
        }

        await assertNoErrors(errors, 'watchlist unavailable-storage fallback');
    } finally {
        await context.close();
    }
}

async function checkResponsiveDesignSystem(page, errors) {
    log('Checking responsive design system and Telegram theme adaptation');

    await page.addInitScript(() => {
        try {
            window.localStorage.removeItem('GeckoClient:theme');
        } catch (err) {
            // Ignore storage access failures in browser privacy modes.
        }

        window.Telegram = {
            WebApp: {
                colorScheme: 'dark',
                themeParams: {
                    bg_color: '#10131a',
                    secondary_bg_color: '#151a23',
                    text_color: '#f4f7fb',
                    hint_color: '#8f9aae',
                    link_color: '#54c8e8',
                    button_color: '#1bb2da',
                    button_text_color: '#071018',
                    header_bg_color: '#10131a',
                    bottom_bar_bg_color: '#151a23',
                    destructive_text_color: '#ff6b6b',
                },
                __events: {},
                onEvent(name, callback) {
                    this.__events[name] = callback;
                },
                ready() {},
                expand() {},
                setHeaderColor(color) {
                    this.__headerColor = color;
                },
                setBackgroundColor(color) {
                    this.__backgroundColor = color;
                },
                setBottomBarColor(color) {
                    this.__bottomBarColor = color;
                },
            },
        };
    });

    await page.setViewportSize({width: 360, height: 760});
    await page.goto(`${baseURL}/`, {waitUntil: 'domcontentloaded'});
    await page.locator('#market-pulse').waitFor({state: 'visible'});
    await page.getByRole('heading', {name: /Market Pulse/i}).first().waitFor({state: 'visible'});
    await page.locator('#market-pulse a', {hasText: 'Bitcoin'}).first().waitFor({state: 'visible'});

    const result = await page.evaluate(() => {
        const root = document.documentElement;
        const app = document.querySelector('.v-application');
        const rootStyles = getComputedStyle(root);
        const appBar = document.querySelector('.v-app-bar');
        const themeColor = document.querySelector('meta[name="theme-color"]');

        return {
            viewportWidth: window.innerWidth,
            scrollWidth: Math.max(root.scrollWidth, document.body.scrollWidth),
            telegramClass: root.classList.contains('tbc-telegram-webview'),
            darkClass: root.classList.contains('tbc-theme-dark') || (app && app.classList.contains('theme--dark')),
            telegramBg: rootStyles.getPropertyValue('--tbc-tg-bg').trim(),
            telegramButton: rootStyles.getPropertyValue('--tbc-tg-button').trim(),
            appBarBg: appBar ? getComputedStyle(appBar).backgroundColor : '',
            themeColor: themeColor ? themeColor.content : '',
            nativeHeaderColor: window.Telegram.WebApp.__headerColor || '',
            nativeBackgroundColor: window.Telegram.WebApp.__backgroundColor || '',
            nativeBottomBarColor: window.Telegram.WebApp.__bottomBarColor || '',
        };
    });

    if (result.scrollWidth > result.viewportWidth) {
        fail(`360px viewport overflowed: scrollWidth ${result.scrollWidth}, viewport ${result.viewportWidth}`);
    }

    if (!result.telegramClass || !result.darkClass) {
        fail(`Telegram webview dark theme did not activate: ${JSON.stringify(result)}`);
    }

    if (result.telegramBg !== '#10131A' || result.telegramButton !== '#1BB2DA') {
        fail(`Telegram CSS theme parameters were not applied: ${JSON.stringify(result)}`);
    }

    if (result.nativeHeaderColor !== '#10131A' || result.nativeBackgroundColor !== '#10131A' || result.nativeBottomBarColor !== '#151A23') {
        fail(`Telegram native color APIs were not synchronized: ${JSON.stringify(result)}`);
    }

    if (!['#10131A', '#1BB2DA'].includes(result.themeColor)) {
        fail(`Theme color meta did not follow Telegram or Vuetify theme: ${JSON.stringify(result)}`);
    }

    await assertNoErrors(errors, 'responsive design system');
}

async function run() {
    startServer();
    await waitForServer();

    const browser = await chromium.launch();
    const context = await browser.newContext();
    const requestLog = {
        globals: [],
        coinsMarkets: [],
        coinDetails: [],
        marketCharts: [],
        exchanges: [],
        trending: [],
        tonDominanceMarkets: [],
        searches: [],
    };
    await installRoutes(context, requestLog);

    const errors = [];
    const directProviderRequests = [];
    const page = await context.newPage();

    page.on('console', message => {
        if (message.type() === 'error') {
            errors.push(`console error: ${message.text()}`);
        }
    });
    page.on('pageerror', error => {
        errors.push(`page error: ${error.message}`);
    });
    page.on('request', request => {
        const host = new URL(request.url()).host;
        if (['api.coingecko.com', 'pro-api.coingecko.com', 'localstorage.one'].includes(host)) {
            directProviderRequests.push(request.url());
        }
    });

    try {
        await checkMarketPulseHome(page, errors, requestLog);
        await checkMarketsList(page, errors, requestLog);
        await checkCoinDetail(page, errors, requestLog);
        await checkExchangesList(page, errors, requestLog);
        await checkSearchInteraction(page, errors, requestLog);
        await checkSearchMobileDialog(page, errors, requestLog);
        await checkWatchlistPersistence(page, errors);
        await checkResponsiveDesignSystem(page, errors);
        await checkWatchlistUnavailableStorageFallback(browser);
        await assertNoDirectProviderRequests(directProviderRequests);
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
