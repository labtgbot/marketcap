(function (window, navigator, document, _, axios, Vue, GeckoClient) {
    'use strict';

    const runtime = GeckoClient.runtime || {};
    const config = runtime.observability || {};
    const enabled = config.clientErrorReporting !== false;
    const endpoint = config.clientErrorEndpoint || '/api/observability/client-error';
    const idPattern = /^[A-Za-z0-9._:-]{1,128}$/;

    function safeIdentifier(value) {
        value = _.isString(value) ? value.trim() : '';
        return idPattern.test(value) ? value : null;
    }

    function newId(prefix) {
        let suffix = '';
        if (window.crypto && window.crypto.getRandomValues) {
            const bytes = new Uint8Array(8);
            window.crypto.getRandomValues(bytes);
            suffix = Array.prototype.map.call(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
        } else {
            suffix = Math.random().toString(16).slice(2) + Date.now().toString(16);
        }
        return prefix + '-' + suffix;
    }

    function redact(value) {
        value = _.isString(value) ? value : String(value || '');
        return value
            .replace(/(api[_-]?key|x[_-]cg[_-](demo|pro)[_-]api[_-]key|token|password|secret|authorization)=([^&\s]+)/gi, '$1=[redacted]')
            .replace(/Bearer\s+[A-Za-z0-9._~+/=-]+/gi, 'Bearer [redacted]');
    }

    function safeText(value, max) {
        value = redact(value).trim();
        max = max || 300;
        return value.length > max ? value.slice(0, max) + '...' : value;
    }

    function safePath(value) {
        if (!value) return null;
        try {
            const url = new URL(value, window.location.href);
            return url.pathname;
        } catch (err) {
            return _.isString(value) && value.charAt(0) === '/' ? value.split('?')[0] : null;
        }
    }

    function requestUrl(config) {
        config = config || {};
        try {
            return (new URL(config.url || '', config.baseURL || window.location.href)).toString();
        } catch (err) {
            return config.url || '';
        }
    }

    function responseRequestId(error, fallback) {
        const response = error && error.response ? error.response : {};
        const headers = response.headers || {};
        const payload = response.data || {};
        return safeIdentifier(headers['x-request-id'])
            || safeIdentifier(headers['X-Request-ID'])
            || safeIdentifier(_.get(payload, 'meta.request_id'))
            || safeIdentifier(fallback)
            || newId('web-api');
    }

    function send(payload) {
        if (!enabled) return;

        const body = JSON.stringify(payload);
        if (navigator.sendBeacon) {
            try {
                if (navigator.sendBeacon(endpoint, new Blob([body], {type: 'application/json'}))) return;
            } catch (err) {
                // Fall through to fetch.
            }
        }

        if (window.fetch) {
            window.fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Request-ID': newId('web-ingest')},
                body: body,
                credentials: 'same-origin',
                keepalive: true
            }).catch(() => {});
        }
    }

    function report(type, detail) {
        detail = detail || {};
        send({
            type: type,
            client_event_id: safeIdentifier(detail.client_event_id) || newId('web-event'),
            request_id: safeIdentifier(detail.request_id),
            message: safeText(detail.message || type),
            source: safePath(detail.source),
            url_path: safePath(detail.url_path || window.location.href),
            route_path: safePath(detail.route_path || (window.location.pathname || '/')),
            api_path: safePath(detail.api_path),
            method: detail.method,
            status: detail.status,
            error_code: detail.error_code,
            duration_ms: detail.duration_ms,
            line: detail.line,
            column: detail.column,
            component: safeText(detail.component || '', 120)
        });
    }

    function reportApiError(error) {
        const requestConfig = error && error.config ? error.config : {};
        const metadata = requestConfig.tonbankcardObservability || {};
        const response = error && error.response ? error.response : {};
        const payload = response.data || {};
        const requestId = responseRequestId(error, metadata.requestId);
        const startedAt = metadata.startedAt || Date.now();

        report('api_error', {
            request_id: requestId,
            message: error && error.message ? error.message : 'API request failed',
            api_path: requestUrl(requestConfig),
            method: requestConfig.method || metadata.method,
            status: response.status || 0,
            error_code: _.get(payload, 'error.code', error && error.code ? error.code : 'request_failed'),
            duration_ms: Math.max(0, Date.now() - startedAt)
        });
    }

    const observability = GeckoClient.observability = {
        nextRequestId: function (scope) {
            return newId(scope || 'web');
        },
        report: report,
        reportApiError: reportApiError,
        instrumentAxiosInstance: function (client) {
            if (!client || !client.interceptors) return client;

            client.interceptors.request.use(function (requestConfig) {
                requestConfig.headers = requestConfig.headers || {};
                const requestId = safeIdentifier(requestConfig.headers['X-Request-ID'])
                    || safeIdentifier(requestConfig.headers['x-request-id'])
                    || observability.nextRequestId('web-api');
                requestConfig.headers['X-Request-ID'] = requestId;
                requestConfig.tonbankcardObservability = {
                    requestId: requestId,
                    startedAt: Date.now(),
                    method: requestConfig.method || 'get'
                };
                return requestConfig;
            });

            client.interceptors.response.use(
                function (response) {
                    return response;
                },
                function (error) {
                    reportApiError(error);
                    return Promise.reject(error);
                }
            );

            return client;
        }
    };

    window.addEventListener('error', function (event) {
        report('boot_error', {
            message: event && event.message ? event.message : 'Frontend boot error',
            source: event && event.filename ? event.filename : window.location.href,
            line: event ? event.lineno : null,
            column: event ? event.colno : null
        });
    });

    window.addEventListener('unhandledrejection', function (event) {
        const reason = event && event.reason ? event.reason : {};
        report('unhandled_rejection', {
            message: reason && reason.message ? reason.message : 'Unhandled frontend promise rejection',
            source: window.location.href
        });
    });

    if (Vue && Vue.config) {
        const previousErrorHandler = Vue.config.errorHandler;
        Vue.config.errorHandler = function (err, vm, info) {
            report('vue_error', {
                message: err && err.message ? err.message : 'Vue render error',
                source: window.location.href,
                component: info || ''
            });

            if (_.isFunction(previousErrorHandler)) {
                previousErrorHandler.call(this, err, vm, info);
            } else if (err) {
                throw err;
            }
        };
    }

})(window, navigator, document, _, axios, Vue, GeckoClient);
