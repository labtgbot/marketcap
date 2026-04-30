(function (window, _, axios, GeckoClient) {
    'use strict';

    const __ = GeckoClient.__;
    const cg = GeckoClient.cg;
    const utils = GeckoClient.utils;
    const validURLString = utils.validURLString;
    const bitcointalkThreadUrl = utils.bitcointalkThreadUrl;
    const getCustomLink = GeckoClient.getCustomLink;

    function validateUrls(list) {
        return (list || []).map(url => validURLString(url)).filter(url => !!url);
    }

    const CoinGecko = window.CoinGecko = {
        baseUrl: 'https://api.coingecko.com/api/v3/',
        cacheMap: new Map(),
        // cache expiration
        cacheClearTimeout: 5 * 60 * 1000,
        cacheKey: function (path, config) {
            return JSON.stringify([path, config]);
        },
        cacheHas: function (path, config) {
            return this.cacheMap.has(this.cacheKey(path, config));
        },
        cacheGet: function (path, config) {
            return this.cacheMap.get(this.cacheKey(path, config));
        },
        cacheSet: function (path, config, data) {
            return this.cacheMap.set(this.cacheKey(path, config), data);
        },
        cacheRegisterClearTimer: function () {
            this.cacheClearTimer = setInterval(() => this.cacheMap.clear(), this.cacheClearTimeout)
        },
        get: function (path, config, consistency, cache) {
            cache = cache || cg.cache;

            // immediately serve cached data
            if (cache && this.cacheHas(path, config)) return Promise.resolve(_.cloneDeep(this.cacheGet(path, config)));

            const client = axios.create({
                baseURL: this.baseUrl,
                timeout: cg.timeout > 0 ? cg.timeout : 0
            });

            return client.get(path, config)
                .then((res) => {
                    let data = res.data

                    // forces a type or transforms
                    if (consistency) {
                        if (_.isArray(consistency)) data = _.isArray(data) ? data : [];
                        else if (_.isFunction(consistency)) data = consistency(data);
                        else if (_.isObject(consistency)) data = _.isObject(data) ? data : {};
                    }

                    // sets cache if enabled
                    if (cache && data) this.cacheSet(path, config, data);

                    return _.cloneDeep(data);
                });
        },
        marketChartDataConsistency: data => {
            // ensures chart data integrity
            if (!data
                || !_.isArray(data.market_caps)
                || !_.isArray(data.prices)
                || !_.isArray(data.total_volumes)
                || data.market_caps.length !== data.prices.length
                || data.market_caps.length !== data.total_volumes.length) {
                return Promise.reject(new Error());
            }
            return data;
        },
        global: function (cache) {
            const consistency = data => data.data || {};

            return this.get('global', undefined, consistency, cache);
        },
        coinsMarkets: function (params, cache) {
            return this.get('coins/markets', {params: params}, [], cache);
        },
        coin: function (id, params, cache) {
            const consistency = currency => {
                currency = currency || {};

                currency.symbol = _.toLower(currency.symbol);
                currency.categories = _.uniq(currency.categories);
                currency.category = currency.categories[0];

                currency.platforms = currency.platforms || {};
                currency.platformList = _.map(currency.platforms, (address, name) => [_.startCase(name), address]).filter(c => c[0] && c[1]);

                const links = currency.links = currency.links || {};
                currency.websiteUrl = validURLString(_.first(links.homepage))
                currency.explorerUrls = validateUrls(links.blockchain_site);
                currency.announcementUrls = validateUrls(links.announcement_url);
                currency.forumUrls = validateUrls(links.official_forum_url);
                currency.chatUrls = validateUrls(links.chat_url);
                currency.redditUrl = validURLString(links.subreddit_url);
                currency.twitterUrl = validURLString(links.twitter_screen_name, 'https://twitter.com/');
                currency.facebookUrl = validURLString(links.facebook_username, 'https://www.facebook.com/');
                currency.bitcointalkId = links.bitcointalk_thread_identifier || null;
                currency.bitcointalkUrl = bitcointalkThreadUrl(currency.bitcointalkId);
                currency.customLinkUrl = getCustomLink('currencies', id);

                links.repos_url = links.repos_url || {};
                currency.githubUrls = validateUrls(links.repos_url.github);
                currency.bitbucketUrls = validateUrls(links.repos_url.bitbucket);

                currency.url = currency.customLinkUrl || currency.websiteUrl;

                return currency;
            };

            return this.get('coins/' + id, {params: params}, consistency, cache);
        },
        coinMarketChart: function (id, params, cache) {
            return this.get('coins/' + id + '/market_chart', {params: params}, this.marketChartDataConsistency, cache);
        },
        coinMarketChartRange: function (id, params, cache) {
            return this.get('coins/' + id + '/market_chart/range', {params: params}, this.marketChartDataConsistency, cache);
        },
        coinTickers: function (id, params, cache) {
            const consistency = data => _.get(data, 'tickers', []);
            return this.get('coins/' + id + '/tickers', {params: params}, consistency, cache)
        },
        exchanges: function (params, cache) {
            return this.get('exchanges', {params: params}, [], cache);
        },
        exchange: function (id, params, cache) {
            const consistency = exchange => {
                exchange = exchange || {};

                exchange.websiteUrl = validURLString(exchange.url);
                exchange.twitterUrl = validURLString(exchange.twitter_handle, 'https://twitter.com/');
                exchange.facebookUrl = validURLString(exchange.facebook_url, 'https://www.facebook.com/');
                exchange.redditUrl = validURLString(exchange.reddit_url, 'https://www.reddit.com/');
                exchange.telegramUrl = validURLString(exchange.telegram_url, 'https://t.me/');
                exchange.otherUrl1 = validURLString(exchange.other_url_1);
                exchange.otherUrl2 = validURLString(exchange.other_url_2);
                exchange.customLinkUrl = getCustomLink('exchanges', id);
                exchange.url = exchange.customLinkUrl || exchange.websiteUrl;

                return exchange;
            };

            return this.get('exchanges/' + id, {params: params}, consistency, cache);
        },
        exchangeTickers: function (id, params, cache) {
            const consistency = data => _.get(data, 'tickers', []);
            return this.get('exchanges/' + id + '/tickers', {params: params}, consistency, cache);
        },
        exchangeVolumeChart: function (id, params, cache) {
            return this.get('exchanges/' + id + '/volume_chart', {params: params}, [], cache);
        },
        search: function (cache) {
            const consistency = search => {
                return {
                    categories: search.categories || [],
                    coins: search.coins || [],
                    exchanges: search.exchanges || [],
                    icos: search.icos || []
                }
            };
            return this.get('search/', undefined, consistency, cache);
        },
        searchTrending: function (cache) {
            const consistency = trending => {
                trending = trending || {};
                trending.exchanges = _.map(trending.exchanges);
                trending.coins = _.map(trending.coins, 'item');
                return trending;
            };

            return this.get('search/trending', undefined, consistency, cache);
        },
        financePlatforms: function (params, cache) {
            const consistency = platforms => {
                return (platforms || []).map(platform => {
                    platform.customLinkUrl = getCustomLink('financePlatforms', platform.name);
                    platform.websiteUrl = validURLString(platform.website_url);
                    platform.url = platform.customLinkUrl || platform.websiteUrl;
                    platform.color = platform.centralized ? 'orange' : 'green';
                    platform.catLabel = platform.centralized ? __( 'CeFi' ) : __( 'DeFi' );
                    return platform;
                })
            };

            return this.get('finance_platforms', {params: params}, consistency, cache);
        },
        financeProducts: function (params, cache) {
            return this.get('finance_products', {params: params}, [], cache);
        },
        derivatives: function (params, cache) {
            return this.get('derivatives', {params: params}, [], cache);
        }
    };

    if (cg.cache) {
        CoinGecko.cacheRegisterClearTimer();
    }

})(window, _, axios, GeckoClient);
