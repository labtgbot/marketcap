'use strict';

/*
 * Shared Playwright network fixtures for the browser-based test suites
 * (tests/browser-smoke.js and tests/accessibility-check.js).
 *
 * Intercepting the data + partner endpoints keeps the browser tests fully
 * deterministic and offline: no live CoinGecko/ChangeNOW request is made, so the
 * tests never flake on provider rate limits and always render real content
 * (markets rows, coin detail, screener results, the exchange widget). The
 * response shapes mirror the gateway contract the frontend expects.
 *
 * installRoutes(context, requestLog, baseURL) registers the handlers and records
 * matched requests into requestLog (arrays are created lazily, so a caller that
 * does not care about request assertions can pass an empty object).
 */

const transparentPixel = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

function requestRecord(url) {
    return {
        path: url.pathname.replace(/^\/api\/market\/?/, '').replace(/\/$/, ''),
        params: Object.fromEntries(url.searchParams.entries()),
    };
}

function record(requestLog, key, value) {
    if (!Array.isArray(requestLog[key])) {
        requestLog[key] = [];
    }
    requestLog[key].push(value);
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

async function installRoutes(context, requestLog, baseURL) {
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
        record(requestLog, 'searches', {
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
        record(requestLog, 'screeners', {
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

    await context.route(`${baseURL}/api/changenow/currencies`, route => {
        return fulfillJson(route, {
            ok: true,
            data: {
                tickers: [
                    'btc', 'eth', 'ltc', 'xrp', 'doge', 'trx', 'sol', 'ada', 'dot', 'link',
                    'avaxc', 'bnbbsc', 'ton', 'usdtton', 'dogs', 'hmstr', 'not', 'cati',
                    'fail',
                ],
            },
            meta: {request_id: 'browser-smoke-changenow'},
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
            record(requestLog, 'globals', requestRecord(url));
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
            record(requestLog, 'trending', requestRecord(url));
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
                record(requestLog, 'tonDominanceMarkets', requestRecord(url));
                const toncoin = marketCurrency(idsParam, 'ton', 'Toncoin', 12, 6.5, -2.4);
                toncoin.market_cap = 7407407340;

                return fulfillMarketJson(route, [toncoin]);
            }

            record(requestLog, 'coinsMarkets', requestRecord(url));
            return fulfillMarketJson(route, [
                marketCurrency('bitcoin', 'btc', 'Bitcoin', 1, 61000, 1.25),
                marketCurrency('toncoin', 'ton', 'Toncoin', 12, 6.5, -2.4),
            ]);
        }

        if (apiPath === 'coins/bitcoin') {
            record(requestLog, 'coinDetails', requestRecord(url));
            return fulfillMarketJson(route, coinDetail('bitcoin', 'btc', 'Bitcoin', 1, 61000));
        }

        if (apiPath === 'coins/toncoin') {
            record(requestLog, 'coinDetails', requestRecord(url));
            return fulfillMarketJson(route, coinDetail('toncoin', 'ton', 'Toncoin', 12, 6.5));
        }

        if (apiPath === 'coins/chart-failure') {
            record(requestLog, 'coinDetails', requestRecord(url));
            return fulfillMarketJson(route, coinDetail('chart-failure', 'fail', 'Chart Failure Coin', 99, 10));
        }

        if (apiPath === 'coins/chart-failure/market_chart') {
            record(requestLog, 'marketCharts', requestRecord(url));
            return fulfillMarketError(route, 502, 'provider_unavailable', 'Provider unavailable');
        }

        if (apiPath === 'coins/unsupported-coin') {
            record(requestLog, 'coinDetails', requestRecord(url));
            return fulfillMarketJson(route, coinDetail('unsupported-coin', '', 'Unsupported Coin', 999, 1));
        }

        if (apiPath === 'coins/unlisted-coin') {
            record(requestLog, 'coinDetails', requestRecord(url));
            return fulfillMarketJson(route, coinDetail('unlisted-coin', 'xyz', 'Unlisted Coin', 998, 2));
        }

        if (apiPath === 'coins/bitcoin/market_chart' || apiPath === 'coins/toncoin/market_chart' || apiPath === 'coins/unsupported-coin/market_chart' || apiPath === 'coins/unlisted-coin/market_chart') {
            record(requestLog, 'marketCharts', requestRecord(url));
            return fulfillMarketJson(route, marketChart());
        }

        if (apiPath === 'exchanges') {
            record(requestLog, 'exchanges', requestRecord(url));
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

module.exports = {
    transparentPixel,
    requestRecord,
    fulfillJson,
    fulfillMarketJson,
    fulfillMarketError,
    fulfillSearchJson,
    fulfillScreenerJson,
    nowSeries,
    marketChart,
    marketCurrency,
    coinDetail,
    installRoutes,
};
