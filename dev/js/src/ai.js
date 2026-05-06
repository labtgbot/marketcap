(function (window, _, axios, GeckoClient) {
    'use strict';

    const config = GeckoClient.aiConfig || {};
    const observability = GeckoClient.observability;
    const advicePattern = /\b(buy|sell|hold|short(?!-term)|long(?!-term)|leverage|take profit|stop loss|position size|all in)\b/i;

    const retryConfig = _.assign(
        {
            maxAttempts: 3,
            baseDelayMs: 600,
            maxDelayMs: 4000,
            retryableReasons: [
                'provider_unavailable',
                'provider_timeout',
                'provider_rate_limited',
                'provider_invalid_json',
                'schema_validation_failed',
                'client_unavailable'
            ]
        },
        _.isObject(config.retry) ? config.retry : {}
    );

    const client = axios.create({
        baseURL: config.apiBaseUrl || '/api/ai',
        timeout: config.timeoutMs || 12000
    });

    if (observability && observability.instrumentAxiosInstance) {
        observability.instrumentAxiosInstance(client);
    }

    function envelopeData(response) {
        const payload = response && response.data ? response.data : {};
        const data = payload && payload.ok === true && Object.prototype.hasOwnProperty.call(payload, 'data')
            ? payload.data
            : payload;

        if (data && _.isObject(data)) {
            data.request_id = _.get(payload, 'meta.request_id', null);
        }

        return data || {};
    }

    function cleanNumber(value) {
        const number = parseFloat(value);
        return _.isFinite(number) ? number : null;
    }

    function validDate(value) {
        const date = new Date(value || 0);
        return GeckoClient.utils.isValidDate(date) ? date : null;
    }

    function marketDataUpdatedAt(meta) {
        return _.get(meta, 'freshness.last_updated_at')
            || _.get(meta, 'freshness.fetched_at')
            || _.get(meta, 'last_updated_at')
            || _.get(meta, 'updated_at')
            || null;
    }

    function marketDataAgeSeconds(meta) {
        const directAge = _.get(meta, 'freshness.market_data_age_seconds');
        if (_.isFinite(parseFloat(directAge))) {
            return Math.max(0, parseInt(directAge, 10));
        }

        const timestamp = marketDataUpdatedAt(meta);
        const date = validDate(timestamp);
        if (!date) return 0;

        return Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
    }

    function containsAdvice(value) {
        if (_.isString(value)) return advicePattern.test(value);
        if (_.isArray(value)) return value.some(item => containsAdvice(item));
        if (_.isObject(value)) return _.values(value).some(item => containsAdvice(item));
        return false;
    }

    function unavailable(reason) {
        return {
            status: 'insight unavailable',
            insight: null,
            reason: reason || 'client_unavailable'
        };
    }

    function isRetryableReason(reason) {
        return _.includes(retryConfig.retryableReasons, reason);
    }

    function retryDelayMs(attempt) {
        const base = Math.max(0, parseInt(retryConfig.baseDelayMs, 10) || 0);
        const max = Math.max(base, parseInt(retryConfig.maxDelayMs, 10) || 0);
        const exponential = base * Math.pow(2, Math.max(0, attempt - 1));
        const jitter = Math.floor(Math.random() * Math.max(1, base / 2));
        return Math.min(max, exponential + jitter);
    }

    function delay(ms) {
        return new Promise(resolve => window.setTimeout(resolve, ms));
    }

    function insightAttempt(body) {
        return client.post('insight', body)
            .then(response => {
                const data = envelopeData(response);
                if (data.status === 'available' && data.insight && containsAdvice(data.insight)) {
                    return unavailable('unsafe_output_blocked');
                }
                return data;
            })
            .catch(error => {
                const code = _.get(error, 'code') || _.get(error, 'response.status');
                const reason = code === 'ECONNABORTED' ? 'provider_timeout'
                    : code === 429 ? 'provider_rate_limited'
                    : 'provider_unavailable';
                return unavailable(reason);
            });
    }

    function insight(payload, options) {
        if (!payload || !payload.insight_type || !payload.subject) {
            return Promise.resolve(unavailable('missing_context'));
        }

        const body = Object.assign({}, payload, {
            market_data_age_seconds: Math.max(0, parseInt(payload.market_data_age_seconds || 0, 10))
        });

        const opts = _.assign({attempt: 1}, options || {});
        const maxAttempts = Math.max(1, parseInt(retryConfig.maxAttempts, 10) || 1);

        return insightAttempt(body).then(result => {
            const reason = result && result.status !== 'available' ? result.reason : null;
            if (!reason || opts.attempt >= maxAttempts || !isRetryableReason(reason)) {
                if (result && _.isObject(result)) {
                    result.attempts = opts.attempt;
                }
                return result;
            }

            if (_.isFunction(opts.onRetry)) {
                opts.onRetry({attempt: opts.attempt, reason: reason});
            }

            return delay(retryDelayMs(opts.attempt))
                .then(() => insight(payload, _.assign({}, opts, {attempt: opts.attempt + 1})));
        });
    }

    function feedback(insightPayload, feedbackType, context, sourceRoute) {
        insightPayload = insightPayload || {};
        context = context || {};

        if (!insightPayload.id) {
            return Promise.reject(new Error('missing_insight_id'));
        }

        const provider = insightPayload.provider || {};
        const body = {
            feedback_type: feedbackType,
            insight_type: insightPayload.type || context.insight_type,
            insight_id: insightPayload.id,
            subject: context.subject,
            provider: provider.name || _.get(context, 'provider.name'),
            model: provider.model_id || _.get(context, 'provider.model_id'),
            prompt_version: insightPayload.prompt_version || provider.prompt_version || _.get(context, 'prompt_version'),
            route_path: window.location.pathname || '/',
            source_route: sourceRoute,
            surface: GeckoClient.analytics ? GeckoClient.analytics.surface() : 'public_web',
            market_data_age_seconds: cleanNumber(_.get(insightPayload, 'freshness.market_data_age_seconds')) || context.market_data_age_seconds || 0,
            metadata: {
                card_title: context.card_title || null,
                source_route: sourceRoute || null,
                request_id: insightPayload.request_id || null
            }
        };

        return client.post('feedback', body).then(response => envelopeData(response));
    }

    function marketCurrencySnapshot(currency) {
        return {
            id: _.get(currency, 'id', null),
            symbol: _.get(currency, 'symbol', null),
            name: _.get(currency, 'name', null),
            rank: _.get(currency, 'market_cap_rank', null),
            price: cleanNumber(_.get(currency, 'current_price', _.get(currency, 'currentPrice'))),
            change_24h: cleanNumber(_.get(currency, 'price_change_percentage_24h_in_currency', _.get(currency, 'change24hPercent'))),
            market_cap: cleanNumber(_.get(currency, 'market_cap', _.get(currency, 'marketCap'))),
            volume_24h: cleanNumber(_.get(currency, 'total_volume', _.get(currency, 'totalVolume')))
        };
    }

    GeckoClient.ai = {
        containsAdvice: containsAdvice,
        feedback: feedback,
        insight: insight,
        isRetryableReason: isRetryableReason,
        retryConfig: retryConfig,
        marketCurrencySnapshot: marketCurrencySnapshot,
        marketDataAgeSeconds: marketDataAgeSeconds,
        marketDataUpdatedAt: marketDataUpdatedAt
    };

})(window, _, axios, GeckoClient);
