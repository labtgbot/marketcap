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

function assertURLParam(urlString, param, expected, label) {
    const actual = new URL(urlString).searchParams.get(param);
    assertEqual(actual, expected, label);
}

function lastRequest(requests, label) {
    if (!requests.length) {
        fail(`${label}: expected a matching API request`);
    }

    return requests[requests.length - 1];
}

async function waitForLoggedRequest(requests, predicate, label) {
    const deadline = Date.now() + 5000;

    while (Date.now() < deadline) {
        const request = requests.find(predicate);
        if (request) {
            return request;
        }

        await new Promise(resolve => setTimeout(resolve, 100));
    }

    fail(`${label}: expected a matching API request`);
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

function fulfillMarketError(route, status, code, message) {
    return route.fulfill({
        status,
        contentType: 'application/json',
        body: JSON.stringify({
            ok: false,
            error: {
                code,
                message,
            },
            meta: {
                request_id: 'browser-smoke-error',
            },
        }),
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

function fulfillScreenerJson(route, data) {
    return fulfillJson(route, {
        ok: true,
        data,
        meta: {
            request_id: 'browser-smoke-screener',
            route_group: 'screener',
            screener: {
                filters: data.filters,
                source_count: data.summary.source_count,
                result_count: data.summary.result_count,
                csv_export_enabled: false,
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
            TONBANKCARD_FEATURE_CHANGENOW: 'true',
            TONBANKCARD_FEATURE_TON_CONNECT: 'true',
            CHANGENOW_LINK_ID: '3cc0024a18fd9d',
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
    await context.route('https://unpkg.com/@tonconnect/ui@2.4.4/dist/tonconnect-ui.min.js', route => {
        return route.fulfill({
            status: 200,
            contentType: 'application/javascript',
            body: `
                window.TON_CONNECT_UI = {
                    TonConnectUI: function TonConnectUI(options) {
                        this.options = options || {};
                        this.wallet = null;
                        this.__listeners = [];
                        this.onStatusChange = callback => {
                            this.__listeners.push(callback);
                            return () => {
                                this.__listeners = this.__listeners.filter(listener => listener !== callback);
                            };
                        };
                        this.connectWallet = () => {
                            this.wallet = {
                                account: {
                                    address: 'EQD1111111111111111111111111111111111111111111111111111111111111111',
                                    chain: '-239',
                                },
                                device: {
                                    appName: 'Tonkeeper',
                                    appVersion: '5.0.0',
                                    platform: 'ios',
                                    features: ['SendTransaction', 'SignData'],
                                },
                                provider: 'tonconnect',
                                walletInfo: {
                                    name: 'Tonkeeper',
                                },
                            };
                            this.__listeners.forEach(listener => listener(this.wallet));
                            return Promise.resolve(this.wallet);
                        };
                        this.disconnect = () => {
                            this.wallet = null;
                            this.__listeners.forEach(listener => listener(null));
                            return Promise.resolve();
                        };
                    },
                };
            `,
        });
    });

    await context.route('https://changenow.io/embeds/**', route => {
        if (route.request().url().endsWith('.js')) {
            return route.fulfill({
                status: 200,
                contentType: 'application/javascript',
                body: 'window.__tbcChangeNowConnectorLoaded = true;',
            });
        }

        return route.fulfill({
            status: 200,
            contentType: 'text/html',
            body: '<!doctype html><title>ChangeNOW test widget</title><main>ChangeNOW widget placeholder</main>',
        });
    });

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

    await context.route(`${baseURL}/api/screener/markets**`, route => {
        const url = new URL(route.request().url());
        requestLog.screeners.push({
            path: url.pathname,
            params: Object.fromEntries(url.searchParams.entries()),
        });

        const filters = {
            vs_currency: url.searchParams.get('vs_currency') || 'usd',
            ton_tag: url.searchParams.get('ton_tag') || '',
            market_cap_min: url.searchParams.get('market_cap_min') || null,
            watchlist: url.searchParams.get('watchlist') || 'all',
        };
        const toncoin = marketCurrency('toncoin', 'ton', 'Toncoin', 12, 6.5, 4.2);
        toncoin.ton_asset = {
            id: 'toncoin',
            coin_id: 'toncoin',
            name: 'Toncoin',
            verification_state: 'verified',
            tags: ['ton_ecosystem', 'layer_1'],
        };
        toncoin.watchlist = {
            watched: false,
            source: 'client_snapshot',
        };
        toncoin.sentiment = {
            score: 42,
            label: 'bullish',
            confidence: 0.82,
            source: 'deterministic_market_movement',
        };
        toncoin.sentiment_score = 42;
        toncoin.exchange_availability = {
            requested: url.searchParams.get('exchange') || '',
            matched: url.searchParams.get('exchange') ? true : null,
            checked: !!url.searchParams.get('exchange'),
        };

        return fulfillScreenerJson(route, {
            filters,
            sort: {
                key: url.searchParams.get('sort') || 'market_cap_rank',
                direction: url.searchParams.get('direction') || 'asc',
            },
            summary: {
                source_count: 2,
                result_count: 1,
                watched_count: 0,
                ton_count: 1,
                average_sentiment: 42,
                csv_export_enabled: false,
            },
            filter_options: {
                ton_tags: ['ton_ecosystem', 'stablecoin'],
                categories: [],
                exchanges: [{id: 'binance', name: 'Binance'}],
            },
            items: [toncoin],
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
            const idsParam = url.searchParams.get('ids');
            if (idsParam === 'toncoin' || idsParam === 'the-open-network') {
                requestLog.tonDominanceMarkets.push(requestRecord(url));
                const toncoin = marketCurrency(idsParam, 'ton', 'Toncoin', 12, 6.5, -2.4);
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

        if (apiPath === 'coins/chart-failure') {
            requestLog.coinDetails.push(requestRecord(url));
            return fulfillMarketJson(route, coinDetail('chart-failure', 'fail', 'Chart Failure Coin', 99, 10));
        }

        if (apiPath === 'coins/chart-failure/market_chart') {
            requestLog.marketCharts.push(requestRecord(url));
            return fulfillMarketError(route, 502, 'provider_unavailable', 'Provider unavailable');
        }

        if (apiPath === 'coins/unsupported-coin') {
            requestLog.coinDetails.push(requestRecord(url));
            return fulfillMarketJson(route, coinDetail('unsupported-coin', '', 'Unsupported Coin', 999, 1));
        }

        if (apiPath === 'coins/unlisted-coin') {
            requestLog.coinDetails.push(requestRecord(url));
            return fulfillMarketJson(route, coinDetail('unlisted-coin', 'xyz', 'Unlisted Coin', 998, 2));
        }

        if (apiPath === 'coins/bitcoin/market_chart' || apiPath === 'coins/toncoin/market_chart' || apiPath === 'coins/unsupported-coin/market_chart' || apiPath === 'coins/unlisted-coin/market_chart') {
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

function removeExpectedChartFailureConsoleError(errors) {
    const index = errors.findIndex(message => /Failed to load resource: the server responded with a status of 502/.test(message));
    if (index >= 0) {
        errors.splice(index, 1);
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

    const eagerECharts = await page.evaluate(() => !!window.echarts);
    if (eagerECharts) {
        fail('ECharts loaded before a chart route requested it');
    }

    const tonDominanceRequest = lastRequest(requestLog.tonDominanceMarkets, 'TON dominance coins request');
    assertEqual(tonDominanceRequest.params.ids, 'the-open-network', 'TON dominance ids');
    assertEqual(tonDominanceRequest.params.vs_currency, 'usd', 'TON dominance vs_currency');

    const pulseRequests = requestLog.coinsMarkets.filter(record => !record.params.ids);
    if (!pulseRequests.length) {
        fail('market pulse coins request: expected a matching API request');
    }
    const request = pulseRequests[pulseRequests.length - 1];
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

async function checkAdvancedScreener(page, errors, requestLog) {
    log('Checking advanced screener render and mobile filters');
    requestLog.screeners = [];

    await page.setViewportSize({width: 1280, height: 900});
    await page.goto(`${baseURL}/screener?ton_tag=ton_ecosystem&market_cap_min=1000000000`, {waitUntil: 'domcontentloaded'});
    await page.locator('#screener').waitFor({state: 'visible'});
    await page.locator('#screener-results-table').waitFor({state: 'visible'});
    await page.locator('#screener-results-table tbody tr', {hasText: 'Toncoin'}).first().waitFor({state: 'visible'});
    await page.getByText('Preset sync requires', {exact: false}).first().waitFor({state: 'visible'});

    const request = lastRequest(requestLog.screeners, 'advanced screener request');
    assertEqual(request.params.vs_currency, 'usd', 'advanced screener vs_currency');
    assertEqual(request.params.ton_tag, 'ton_ecosystem', 'advanced screener TON tag');
    assertEqual(request.params.market_cap_min, '1000000000', 'advanced screener market cap filter');

    await page.setViewportSize({width: 390, height: 844});
    await page.goto(`${baseURL}/screener?watchlist=watched`, {waitUntil: 'domcontentloaded'});
    await page.locator('#screener').waitFor({state: 'visible'});
    await page.getByRole('button', {name: 'Open filters'}).click();
    await page.locator('.screener-filter-drawer').waitFor({state: 'visible'});

    const mobileResult = await page.evaluate(() => {
        const root = document.documentElement;
        const drawer = document.querySelector('.screener-filter-drawer');
        const drawerBox = drawer ? drawer.getBoundingClientRect() : null;
        return {
            viewportWidth: window.innerWidth,
            scrollWidth: Math.max(root.scrollWidth, document.body.scrollWidth),
            drawerWidth: drawerBox ? drawerBox.width : 0,
        };
    });
    if (mobileResult.scrollWidth > mobileResult.viewportWidth || mobileResult.drawerWidth > mobileResult.viewportWidth) {
        fail(`Mobile screener filters overflowed: ${JSON.stringify(mobileResult)}`);
    }

    await page.setViewportSize({width: 1280, height: 900});
    await assertNoErrors(errors, 'advanced screener');
}

async function checkCoinDetail(page, errors, requestLog) {
    log('Checking coin detail, chart tab, and ChangeNOW widget regression coverage');
    requestLog.coinDetails = [];
    requestLog.marketCharts = [];

    await page.goto(`${baseURL}/currency/bitcoin`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.getByText('Bitcoin Price', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByText('Rank #1', {exact: false}).first().waitFor({state: 'visible'});
    await page.locator('.gc-currency-chart-container canvas').first().waitFor({state: 'visible'});
    await page.locator('.currency-exchange-widget[data-widget-status="ready"]').waitFor({state: 'visible'});
    await page.locator('.currency-exchange-widget iframe').waitFor({state: 'attached'});
    await page.getByRole('button', {name: /Add Bitcoin to Watchlist/i}).first().waitFor({state: 'visible'});
    await page.getByRole('button', {name: /Create alert/i}).first().waitFor({state: 'visible'});
    await page.getByRole('button', {name: /Share Bitcoin/i}).first().waitFor({state: 'visible'});

    const bitcoinWidgetSrc = await page.locator('.currency-exchange-widget iframe').first().getAttribute('src');
    assertURLParam(bitcoinWidgetSrc, 'from', 'btc', 'Bitcoin widget from asset');
    assertURLParam(bitcoinWidgetSrc, 'to', 'usdtton', 'Bitcoin widget target asset');
    assertURLParam(bitcoinWidgetSrc, 'link_id', '3cc0024a18fd9d', 'Bitcoin widget partner link id');
    assertURLParam(bitcoinWidgetSrc, 'primaryColor', '1bb2da', 'Bitcoin widget primary color');
    assertURLParam(bitcoinWidgetSrc, 'backgroundColor', 'f6fafd', 'Bitcoin widget background color');

    const converterCount = await page.locator('.gc-currency-converter').count();
    assertEqual(converterCount, 0, 'legacy converter count');

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

async function checkCoinChartVisualization(page, errors, requestLog) {
    log('Checking advanced coin chart visualization controls');
    requestLog.marketCharts = [];

    await page.goto(`${baseURL}/currency/bitcoin`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.locator('.gc-currency-chart-container canvas').first().waitFor({state: 'visible'});
    await page.locator('.gc-currency-chart-summary', {hasText: 'Start'}).waitFor({state: 'visible'});

    const chartRuntime = await page.evaluate(() => ({
        loaded: !!window.echarts,
        darkThemeRegistered: !!(window.echarts && window.echarts.__tbcDarkThemeRegistered),
    }));
    if (!chartRuntime.loaded || !chartRuntime.darkThemeRegistered) {
        fail(`ECharts lazy runtime did not load and register theme: ${JSON.stringify(chartRuntime)}`);
    }

    await page.getByRole('button', {name: 'Volume'}).click();
    await page.locator('.gc-currency-chart-summary', {hasText: 'High'}).waitFor({state: 'visible'});

    await page.getByRole('button', {name: 'Relative'}).click();
    const summaryText = await page.locator('.gc-currency-chart .tbc-sr-only').first().textContent();
    if (!summaryText || !/Relative/i.test(summaryText)) {
        fail(`Chart accessible summary did not update for relative performance: ${summaryText || ''}`);
    }

    await page.getByRole('button', {name: '3M'}).click();
    const rangeRequest = await waitForLoggedRequest(
        requestLog.marketCharts,
        request => request.path === 'coins/bitcoin/market_chart' && request.params.days === '90',
        'coin chart 3M range request'
    );
    assertEqual(rangeRequest.params.vs_currency, 'usd', 'coin chart 3M vs_currency');

    await page.locator('.gc-currency-chart-container[role="img"][aria-describedby]').waitFor({state: 'visible'});
    await assertNoErrors(errors, 'advanced coin chart visualization');
}

async function checkCoinChartFailureFallback(page, errors, requestLog) {
    log('Checking coin chart failure fallback');
    requestLog.coinDetails = [];
    requestLog.marketCharts = [];

    await page.goto(`${baseURL}/currency/chart-failure`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.getByText('Chart Failure Coin Price', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByText('Market chart is unavailable', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByRole('button', {name: 'Retry'}).first().waitFor({state: 'visible'});
    await page.locator('.currency-exchange-widget').waitFor({state: 'visible'});

    const detailRequest = lastRequest(requestLog.coinDetails, 'chart failure coin detail request');
    assertEqual(detailRequest.path, 'coins/chart-failure', 'chart failure coin detail path');

    const chartRequest = lastRequest(requestLog.marketCharts, 'chart failure market chart request');
    assertEqual(chartRequest.path, 'coins/chart-failure/market_chart', 'chart failure chart path');
    removeExpectedChartFailureConsoleError(errors);
    await assertNoErrors(errors, 'coin chart failure fallback');
}

async function checkToncoinChangeNowDefaults(page, errors, requestLog) {
    log('Checking Toncoin ChangeNOW defaults and TON indicators');
    requestLog.coinDetails = [];

    await page.goto(`${baseURL}/currency/toncoin`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.getByText('Toncoin Price', {exact: false}).first().waitFor({state: 'visible'});
    await page.locator('.currency-ton-asset-chip', {hasText: 'TON ecosystem'}).waitFor({state: 'visible'});
    await page.locator('.currency-exchange-widget[data-widget-status="ready"]').waitFor({state: 'visible'});

    const tonWidgetSrc = await page.locator('.currency-exchange-widget iframe').first().getAttribute('src');
    assertURLParam(tonWidgetSrc, 'from', 'ton', 'Toncoin widget from asset');
    assertURLParam(tonWidgetSrc, 'to', 'usdtton', 'Toncoin widget target asset');
    assertURLParam(tonWidgetSrc, 'link_id', '3cc0024a18fd9d', 'Toncoin widget partner link id');
    assertURLParam(tonWidgetSrc, 'primaryColor', '1bb2da', 'Toncoin widget primary color');
    assertURLParam(tonWidgetSrc, 'backgroundColor', 'f6fafd', 'Toncoin widget background color');

    const detailRequest = lastRequest(requestLog.coinDetails, 'Toncoin detail request');
    assertEqual(detailRequest.path, 'coins/toncoin', 'Toncoin detail path');
    await assertNoErrors(errors, 'Toncoin ChangeNOW defaults');
}

async function checkUnsupportedCoinFallback(page, errors, requestLog) {
    log('Checking unsupported coin ChangeNOW fallback');
    requestLog.coinDetails = [];

    await page.goto(`${baseURL}/currency/unsupported-coin`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.getByText('Unsupported Coin Price', {exact: false}).first().waitFor({state: 'visible'});
    await page.locator('.currency-exchange-widget[data-widget-status="unsupported"]').waitFor({state: 'visible'});
    await page.getByText('ChangeNOW does not list this asset for the embedded widget yet.', {exact: false}).first().waitFor({state: 'visible'});

    const frameCount = await page.locator('.currency-exchange-widget iframe').count();
    assertEqual(frameCount, 0, 'unsupported coin iframe count');

    const detailRequest = lastRequest(requestLog.coinDetails, 'unsupported coin detail request');
    assertEqual(detailRequest.path, 'coins/unsupported-coin', 'unsupported coin detail path');
    await assertNoErrors(errors, 'unsupported coin ChangeNOW fallback');
}

async function checkUnlistedCoinFallback(page, errors, requestLog) {
    log('Checking unlisted coin with non-empty symbol does not show ChangeNOW widget');
    requestLog.coinDetails = [];

    await page.goto(`${baseURL}/currency/unlisted-coin`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.getByText('Unlisted Coin Price', {exact: false}).first().waitFor({state: 'visible'});
    await page.locator('.currency-exchange-widget[data-widget-status="unsupported"]').waitFor({state: 'visible'});
    await page.getByText('ChangeNOW does not list this asset for the embedded widget yet.', {exact: false}).first().waitFor({state: 'visible'});

    const frameCount = await page.locator('.currency-exchange-widget iframe').count();
    assertEqual(frameCount, 0, 'unlisted coin iframe count');

    const detailRequest = lastRequest(requestLog.coinDetails, 'unlisted coin detail request');
    assertEqual(detailRequest.path, 'coins/unlisted-coin', 'unlisted coin detail path');
    await assertNoErrors(errors, 'unlisted coin ChangeNOW fallback');
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

async function checkTonConnectWalletProfile(page, errors) {
    log('Checking TON Connect wallet profile');

    await page.setViewportSize({width: 390, height: 844});
    await page.goto(`${baseURL}/wallet`, {waitUntil: 'domcontentloaded'});
    await page.locator('#wallet-profile').waitFor({state: 'visible'});
    await page.getByRole('heading', {name: /Wallet Profile/i}).first().waitFor({state: 'visible'});
    await page.getByText('Private keys and seed phrases stay in your wallet', {exact: false}).waitFor({state: 'visible'});
    await page.getByRole('button', {name: /Connect TON wallet/i}).waitFor({state: 'visible'});

    const beforeConnect = await page.evaluate(() => ({
        enabled: window.GeckoClient.tonConnect.enabled,
        manifestUrl: window.GeckoClient.tonConnect.manifestUrl,
        sdkUrl: window.GeckoClient.tonConnect.sdkUrl,
        storage: window.localStorage.getItem('TONBANKCARD:ton-connect-wallet:v1'),
    }));

    if (!beforeConnect.enabled || !/tonconnect-manifest\.json$/.test(beforeConnect.manifestUrl) || !/@tonconnect\/ui@2\.4\.4/.test(beforeConnect.sdkUrl)) {
        fail(`TON Connect runtime config was not exposed safely: ${JSON.stringify(beforeConnect)}`);
    }
    if (beforeConnect.storage) {
        fail(`TON Connect wallet storage was not empty before connect: ${beforeConnect.storage}`);
    }

    await page.getByRole('button', {name: /Connect TON wallet/i}).click();
    await page.getByText('Connected', {exact: true}).waitFor({state: 'visible'});
    await page.getByText('Tonkeeper', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByText('Mainnet', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByText('Send Transaction', {exact: false}).first().waitFor({state: 'visible'});
    await page.getByRole('button', {name: /Disconnect wallet/i}).waitFor({state: 'visible'});
    await assertMobileChromeLayout(page, 'TON Connect wallet profile');
    await page.screenshot({
        path: path.join(logDir, 'ton-connect-wallet-profile.png'),
        animations: 'disabled',
    });

    const storedAfterConnect = await page.evaluate(() => {
        const raw = window.localStorage.getItem('TONBANKCARD:ton-connect-wallet:v1');
        return raw ? JSON.parse(raw) : null;
    });

    if (!storedAfterConnect || storedAfterConnect.network !== 'mainnet' || storedAfterConnect.app_name !== 'Tonkeeper') {
        fail(`Connected wallet snapshot was not stored: ${JSON.stringify(storedAfterConnect)}`);
    }

    const serialized = JSON.stringify(storedAfterConnect);
    if (/private|seed|mnemonic/i.test(serialized)) {
        fail(`Connected wallet snapshot contained secret-shaped data: ${serialized}`);
    }

    await page.getByRole('button', {name: /Disconnect wallet/i}).click();
    await page.getByRole('button', {name: /Connect TON wallet/i}).waitFor({state: 'visible'});
    await page.getByText('Disconnected', {exact: true}).waitFor({state: 'visible'});

    const storedAfterDisconnect = await page.evaluate(() => window.localStorage.getItem('TONBANKCARD:ton-connect-wallet:v1'));
    if (storedAfterDisconnect) {
        fail(`Connected wallet snapshot remained after disconnect: ${storedAfterDisconnect}`);
    }

    await assertNoErrors(errors, 'TON Connect wallet profile');
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
        screeners: [],
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

async function assertMobileChromeLayout(page, label, options = {}) {
    const layout = await page.evaluate(() => {
        const box = selector => {
            const element = document.querySelector(selector);
            if (!element) {
                return {visible: false};
            }

            const style = window.getComputedStyle(element);
            const rect = element.getBoundingClientRect();
            return {
                visible: style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0,
                top: Math.round(rect.top),
                right: Math.round(rect.right),
                bottom: Math.round(rect.bottom),
                left: Math.round(rect.left),
                width: Math.round(rect.width),
                height: Math.round(rect.height),
            };
        };

        return {
            viewportWidth: window.innerWidth,
            viewportHeight: window.innerHeight,
            scrollWidth: Math.max(document.documentElement.scrollWidth, document.body.scrollWidth),
            nav: box('.v-app-bar__nav-icon'),
            brand: box('.tbc-mobile-brand'),
            search: box('.gc-search-trigger'),
            install: box('.tbc-pwa-install-button'),
            bottomNavigation: box('.v-bottom-navigation'),
        };
    });

    if (layout.scrollWidth > layout.viewportWidth) {
        fail(`${label} mobile chrome overflowed horizontally: ${JSON.stringify(layout)}`);
    }

    if (!layout.nav.visible || !layout.brand.visible || !layout.search.visible || !layout.bottomNavigation.visible) {
        fail(`${label} mobile chrome controls were not visible: ${JSON.stringify(layout)}`);
    }

    if (layout.brand.left < layout.nav.right - 2 || layout.search.left < layout.brand.right - 2) {
        fail(`${label} mobile header controls overlapped or were out of order: ${JSON.stringify(layout)}`);
    }

    if (options.expectInstallButton && (!layout.install.visible || layout.install.left < layout.search.right - 2)) {
        fail(`${label} install action was not aligned after search: ${JSON.stringify(layout)}`);
    }

    if (Math.abs(layout.bottomNavigation.bottom - layout.viewportHeight) > 2 || layout.bottomNavigation.top < layout.viewportHeight - 96) {
        fail(`${label} bottom navigation was not pinned to the viewport bottom: ${JSON.stringify(layout)}`);
    }
}

async function checkPwaMobileWeb(page, errors) {
    log('Checking PWA mobile web shell and install prompt handling');

    await page.setViewportSize({width: 390, height: 844});
    await page.goto(`${baseURL}/`, {waitUntil: 'domcontentloaded'});
    await page.locator('#market-pulse').waitFor({state: 'visible'});
    await page.getByRole('heading', {name: /Market Pulse/i}).first().waitFor({state: 'visible'});

    const pwaBeforePrompt = await page.evaluate(() => {
        window.__tbcInstallPromptCalled = false;
        const event = new Event('beforeinstallprompt', {cancelable: true});
        Object.defineProperty(event, 'prompt', {
            value: () => {
                window.__tbcInstallPromptCalled = true;
                return Promise.resolve();
            },
        });
        Object.defineProperty(event, 'userChoice', {
            value: Promise.resolve({outcome: 'accepted'}),
        });
        window.dispatchEvent(event);

        return {
            installAvailable: window.GeckoClient.pwa.installAvailable,
            serviceWorkerUrl: window.GeckoClient.pwa.serviceWorkerUrl,
            telegramClass: document.documentElement.classList.contains('tbc-telegram-webview'),
        };
    });

    if (!pwaBeforePrompt.installAvailable || !/service-worker\.js$/.test(pwaBeforePrompt.serviceWorkerUrl)) {
        fail(`PWA install prompt was not captured: ${JSON.stringify(pwaBeforePrompt)}`);
    }

    if (pwaBeforePrompt.telegramClass) {
        fail(`Normal mobile web shell activated Telegram class: ${JSON.stringify(pwaBeforePrompt)}`);
    }

    await page.getByRole('button', {name: /Install app/i}).waitFor({state: 'visible'});
    await assertMobileChromeLayout(page, 'PWA mobile web', {expectInstallButton: true});
    await page.screenshot({path: path.join(logDir, 'pwa-mobile-web.png')});

    const pwaAfterPrompt = await page.evaluate(async () => {
        const accepted = await window.GeckoClient.pwa.promptInstall();
        return {
            accepted,
            promptCalled: window.__tbcInstallPromptCalled,
            installAvailable: window.GeckoClient.pwa.installAvailable,
        };
    });

    if (!pwaAfterPrompt.accepted || !pwaAfterPrompt.promptCalled || pwaAfterPrompt.installAvailable) {
        fail(`PWA install prompt did not complete cleanly: ${JSON.stringify(pwaAfterPrompt)}`);
    }

    await assertNoErrors(errors, 'PWA mobile web');
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
                viewportHeight: 760,
                viewportStableHeight: 720,
                isExpanded: true,
                isFullscreen: false,
                safeAreaInset: {
                    top: 12,
                    right: 0,
                    bottom: 20,
                    left: 0,
                },
                contentSafeAreaInset: {
                    top: 4,
                    right: 0,
                    bottom: 8,
                    left: 0,
                },
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
                requestFullscreen() {
                    this.__requestFullscreen = true;
                    this.isFullscreen = true;
                },
                setHeaderColor(color) {
                    this.__headerColor = color;
                },
                setBackgroundColor(color) {
                    this.__backgroundColor = color;
                },
                setBottomBarColor(color) {
                    this.__bottomBarColor = color;
                },
                BackButton: {
                    __visible: false,
                    show() {
                        this.__visible = true;
                    },
                    hide() {
                        this.__visible = false;
                    },
                    onClick(callback) {
                        this.__callback = callback;
                    },
                },
                MainButton: {
                    setParams(params) {
                        this.__params = params;
                    },
                    show() {
                        this.__visible = true;
                    },
                },
                SecondaryButton: {
                    setParams(params) {
                        this.__params = params;
                    },
                    show() {
                        this.__visible = true;
                    },
                },
                HapticFeedback: {
                    impactOccurred(style) {
                        this.__impact = style;
                    },
                    notificationOccurred(type) {
                        this.__notification = type;
                    },
                    selectionChanged() {
                        this.__selection = true;
                    },
                },
                shareToStory(mediaUrl, params) {
                    this.__story = {mediaUrl, params};
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
        window.GeckoClient.telegram.hapticImpact('medium');
        window.GeckoClient.telegram.configureMainButton({text: 'Track'});
        window.GeckoClient.telegram.configureSecondaryButton({text: 'Share'});
        window.GeckoClient.telegram.shareToStory('https://example.com/story.png', {text: 'TONBANKCARD'});

        return {
            viewportWidth: window.innerWidth,
            scrollWidth: Math.max(root.scrollWidth, document.body.scrollWidth),
            telegramClass: root.classList.contains('tbc-telegram-webview'),
            expandedClass: root.classList.contains('tbc-telegram-expanded'),
            darkClass: root.classList.contains('tbc-theme-dark') || (app && app.classList.contains('theme--dark')),
            telegramBg: rootStyles.getPropertyValue('--tbc-tg-bg').trim(),
            telegramButton: rootStyles.getPropertyValue('--tbc-tg-button').trim(),
            viewportHeight: rootStyles.getPropertyValue('--tbc-viewport-height').trim(),
            safeAreaTop: rootStyles.getPropertyValue('--tbc-safe-area-top').trim(),
            safeAreaBottom: rootStyles.getPropertyValue('--tbc-safe-area-bottom').trim(),
            contentSafeAreaBottom: rootStyles.getPropertyValue('--tbc-content-safe-area-bottom').trim(),
            appBarBg: appBar ? getComputedStyle(appBar).backgroundColor : '',
            themeColor: themeColor ? themeColor.content : '',
            nativeHeaderColor: window.Telegram.WebApp.__headerColor || '',
            nativeBackgroundColor: window.Telegram.WebApp.__backgroundColor || '',
            nativeBottomBarColor: window.Telegram.WebApp.__bottomBarColor || '',
            requestFullscreen: window.Telegram.WebApp.__requestFullscreen === true,
            hapticImpact: window.Telegram.WebApp.HapticFeedback.__impact || '',
            mainButtonVisible: window.Telegram.WebApp.MainButton.__visible === true,
            mainButtonText: window.Telegram.WebApp.MainButton.__params ? window.Telegram.WebApp.MainButton.__params.text : '',
            secondaryButtonVisible: window.Telegram.WebApp.SecondaryButton.__visible === true,
            secondaryButtonText: window.Telegram.WebApp.SecondaryButton.__params ? window.Telegram.WebApp.SecondaryButton.__params.text : '',
            storyMediaUrl: window.Telegram.WebApp.__story ? window.Telegram.WebApp.__story.mediaUrl : '',
            hasViewportEvent: typeof window.Telegram.WebApp.__events.viewportChanged === 'function',
            hasSafeAreaEvent: typeof window.Telegram.WebApp.__events.safeAreaChanged === 'function',
        };
    });

    if (result.scrollWidth > result.viewportWidth) {
        fail(`360px viewport overflowed: scrollWidth ${result.scrollWidth}, viewport ${result.viewportWidth}`);
    }

    if (!result.telegramClass || !result.darkClass) {
        fail(`Telegram webview dark theme did not activate: ${JSON.stringify(result)}`);
    }

    if (!result.expandedClass || result.viewportHeight !== '760px' || result.safeAreaTop !== '12px' || result.safeAreaBottom !== '20px' || result.contentSafeAreaBottom !== '8px') {
        fail(`Telegram viewport or safe-area values were not applied: ${JSON.stringify(result)}`);
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

    if (!result.requestFullscreen || result.hapticImpact !== 'medium' || !result.mainButtonVisible || result.mainButtonText !== 'Track' || !result.secondaryButtonVisible || result.secondaryButtonText !== 'Share' || result.storyMediaUrl !== 'https://example.com/story.png') {
        fail(`Telegram native controls were not wrapped correctly: ${JSON.stringify(result)}`);
    }

    if (!result.hasViewportEvent || !result.hasSafeAreaEvent) {
        fail(`Telegram viewport/safe-area events were not registered: ${JSON.stringify(result)}`);
    }

    await assertMobileChromeLayout(page, 'Telegram webview');
    await page.screenshot({path: path.join(logDir, 'pwa-telegram-webview.png')});

    await page.goto(`${baseURL}/currency/toncoin`, {waitUntil: 'domcontentloaded'});
    await page.locator('#currency').waitFor({state: 'visible'});
    await page.locator('.currency-ton-asset-chip', {hasText: 'TON ecosystem'}).waitFor({state: 'visible'});
    await page.locator('.currency-exchange-widget[data-widget-status="ready"]').waitFor({state: 'visible'});

    const coinDetailResult = await page.evaluate(() => {
        const root = document.documentElement;
        const widget = document.querySelector('.currency-exchange-widget');
        const widgetBox = widget ? widget.getBoundingClientRect() : null;

        return {
            viewportWidth: window.innerWidth,
            scrollWidth: Math.max(root.scrollWidth, document.body.scrollWidth),
            widgetWidth: widgetBox ? widgetBox.width : 0,
            backButtonVisible: window.Telegram.WebApp.BackButton.__visible === true,
        };
    });

    if (coinDetailResult.scrollWidth > coinDetailResult.viewportWidth) {
        fail(`360px coin detail viewport overflowed: ${JSON.stringify(coinDetailResult)}`);
    }

    if (coinDetailResult.widgetWidth <= 0 || coinDetailResult.widgetWidth > coinDetailResult.viewportWidth) {
        fail(`Mobile ChangeNOW widget was not sized inside the viewport: ${JSON.stringify(coinDetailResult)}`);
    }

    if (!coinDetailResult.backButtonVisible) {
        fail(`Telegram BackButton was not shown on a nested route: ${JSON.stringify(coinDetailResult)}`);
    }

    await page.evaluate(() => {
        window.Telegram.WebApp.BackButton.__callback();
    });
    await page.locator('#market-pulse').waitFor({state: 'visible'});
    const backResult = await page.evaluate(() => ({
        path: window.location.pathname,
        selectionHaptic: window.Telegram.WebApp.HapticFeedback.__selection === true,
    }));

    if (backResult.path !== '/' || !backResult.selectionHaptic) {
        fail(`Telegram BackButton did not navigate back through the app: ${JSON.stringify(backResult)}`);
    }

    await page.goto(`${baseURL}/wallet`, {waitUntil: 'domcontentloaded'});
    await page.locator('#wallet-profile').waitFor({state: 'visible'});
    await page.getByText('Telegram Mini App', {exact: true}).waitFor({state: 'visible'});
    await page.getByText('Private keys and seed phrases stay in your wallet', {exact: false}).waitFor({state: 'visible'});

    const walletProfileResult = await page.evaluate(() => {
        const root = document.documentElement;
        return {
            viewportWidth: window.innerWidth,
            scrollWidth: Math.max(root.scrollWidth, document.body.scrollWidth),
            telegramActive: window.GeckoClient.telegram.active === true,
            featureEnabled: window.GeckoClient.tonConnect.enabled === true,
            surfaceText: document.querySelector('#wallet-profile') ? document.querySelector('#wallet-profile').textContent : '',
        };
    });

    if (walletProfileResult.scrollWidth > walletProfileResult.viewportWidth || !walletProfileResult.telegramActive || !walletProfileResult.featureEnabled || !/Telegram Mini App/.test(walletProfileResult.surfaceText)) {
        fail(`Telegram wallet profile did not preserve the Mini App surface: ${JSON.stringify(walletProfileResult)}`);
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
        screeners: [],
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
        await checkAdvancedScreener(page, errors, requestLog);
        await checkCoinDetail(page, errors, requestLog);
        await checkCoinChartVisualization(page, errors, requestLog);
        await checkCoinChartFailureFallback(page, errors, requestLog);
        await checkToncoinChangeNowDefaults(page, errors, requestLog);
        await checkUnsupportedCoinFallback(page, errors, requestLog);
        await checkUnlistedCoinFallback(page, errors, requestLog);
        await checkExchangesList(page, errors, requestLog);
        await checkSearchInteraction(page, errors, requestLog);
        await checkSearchMobileDialog(page, errors, requestLog);
        await checkWatchlistPersistence(page, errors);
        await checkTonConnectWalletProfile(page, errors);
        await checkPwaMobileWeb(page, errors);
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
