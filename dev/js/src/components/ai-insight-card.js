(function (window, _, Vue, GeckoClient) {
    'use strict';

    const feedbackOptions = [
        {value: 'helpful', label: 'Helpful', icon: 'mdi-thumb-up-outline'},
        {value: 'stale', label: 'Stale', icon: 'mdi-clock-alert-outline'},
        {value: 'wrong', label: 'Wrong', icon: 'mdi-alert-circle-outline'},
        {value: 'unsafe', label: 'Unsafe', icon: 'mdi-shield-alert-outline'}
    ];

    Vue.component('gc-ai-insight-card', {
        template: '#component-ai-insight-card',
        props: {
            title: {
                type: String,
                required: true
            },
            icon: {
                type: String,
                default: 'mdi-brain'
            },
            context: {
                type: Object,
                default: null
            },
            sourceRoute: {
                type: String,
                default: null
            }
        },
        data: function () {
            return {
                loading: false,
                result: null,
                feedbackState: '',
                feedbackSubmitting: false,
                attempts: 0,
                fetchToken: 0
            };
        },
        computed: {
            contextSignature: function () {
                return JSON.stringify(this.context || {});
            },
            canRequest: function () {
                return !!(this.context && this.context.insight_type && this.context.subject);
            },
            insight: function () {
                return this.result && this.result.status === 'available' ? this.result.insight : null;
            },
            unavailableReason: function () {
                const labels = {
                    ai_disabled: 'AI disabled',
                    provider_not_configured: 'Provider not configured',
                    provider_unavailable: 'Provider unavailable',
                    provider_timeout: 'Provider timeout',
                    provider_rate_limited: 'Provider rate limited',
                    provider_invalid_json: 'Provider returned invalid response',
                    feature_disabled: 'Feature disabled',
                    schema_validation_failed: 'Safety validation blocked output',
                    unsafe_output_blocked: 'Safety validation blocked output',
                    missing_context: 'Insight context unavailable',
                    client_unavailable: 'Network unavailable'
                };
                const reason = this.result && this.result.reason ? this.result.reason : '';
                return labels[reason] || (reason ? _.startCase(reason) : 'Insight unavailable');
            },
            canRetry: function () {
                if (this.loading || !this.canRequest || !GeckoClient.ai) return false;
                if (!this.result || this.result.status === 'available') return false;
                return GeckoClient.ai.isRetryableReason(this.result.reason);
            },
            feedbackOptions: function () {
                return feedbackOptions;
            },
            insightShareCard: function () {
                if (!this.insight) return null;

                return {
                    title: this.insight.title || this.title,
                    subtitle: 'AI insight summary',
                    body: this.insight.summary || 'AI market insight summary from TONBANKCARD.',
                    route: GeckoClient.share ? GeckoClient.share.currentRoute() : (window.location.pathname || '/'),
                    campaign: 'ai-insight',
                    context: 'ai_insight',
                    freshness: this.freshnessLabel(this.insight),
                    metrics: [
                        {label: 'Sentiment', value: _.startCase(this.insight.sentiment || 'neutral')},
                        {label: 'Confidence', value: this.confidenceLabel(this.insight.confidence)},
                        {label: 'Source', value: this.sourceRoute || 'market_view'},
                        {label: 'Provider', value: this.insight.provider || 'configured AI'}
                    ]
                };
            }
        },
        watch: {
            contextSignature: {
                immediate: true,
                handler: function () {
                    this.fetchInsight();
                }
            }
        },
        methods: {
            fetchInsight: function () {
                this.feedbackState = '';

                if (!this.canRequest || !GeckoClient.ai) {
                    this.result = null;
                    this.loading = false;
                    return;
                }

                const context = Object.assign({}, this.context, {card_title: this.title});
                const token = ++this.fetchToken;
                this.loading = true;
                this.attempts = 1;

                GeckoClient.ai.insight(context, {
                    onRetry: payload => {
                        if (token !== this.fetchToken) return;
                        this.attempts = (payload && payload.attempt ? payload.attempt : this.attempts) + 1;
                    }
                })
                    .then(result => {
                        if (token !== this.fetchToken) return;
                        result = result || {};
                        if (result.attempts) this.attempts = result.attempts;
                        if (result.insight) {
                            result.insight.provider = result.provider || null;
                            result.insight.prompt_version = result.prompt_version || null;
                            result.insight.request_id = result.request_id || null;
                        }
                        this.result = result;
                    })
                    .catch(() => {
                        if (token !== this.fetchToken) return;
                        this.result = {
                            status: 'insight unavailable',
                            reason: 'client_unavailable'
                        };
                    })
                    .finally(() => {
                        if (token !== this.fetchToken) return;
                        this.loading = false;
                    });
            },
            retryFetch: function () {
                if (!this.canRetry) return;
                this.fetchInsight();
            },
            confidenceLabel: function (confidence) {
                const value = Math.max(0, Math.min(1, parseFloat(confidence) || 0));
                return Math.round(value * 100) + '% confidence';
            },
            confidenceColor: function (confidence) {
                const value = parseFloat(confidence) || 0;
                if (value >= 0.7) return 'success';
                if (value >= 0.4) return 'warning';
                return 'grey';
            },
            sentimentColor: function (sentiment) {
                if (sentiment === 'bullish') return 'success';
                if (sentiment === 'bearish') return 'error';
                if (sentiment === 'mixed') return 'warning';
                return 'grey';
            },
            freshnessLabel: function (insight) {
                const seconds = parseInt(_.get(insight, 'freshness.market_data_age_seconds', 0), 10);
                if (!_.isFinite(seconds) || seconds < 60) return 'Fresh source';
                if (seconds < 3600) return Math.floor(seconds / 60) + 'm source age';
                if (seconds < 86400) return Math.floor(seconds / 3600) + 'h source age';
                return Math.floor(seconds / 86400) + 'd source age';
            },
            submitFeedback: function (feedbackType) {
                if (!this.insight || !GeckoClient.ai || this.feedbackSubmitting) return;

                this.feedbackSubmitting = true;
                this.feedbackState = '';

                GeckoClient.ai.feedback(this.insight, feedbackType, this.context, this.sourceRoute)
                    .then(() => this.feedbackState = 'saved')
                    .catch(() => this.feedbackState = 'failed')
                    .finally(() => this.feedbackSubmitting = false);
            },
            shareInsight: function () {
                if (!this.insight || !GeckoClient.share) return;
                GeckoClient.share.share(this.insightShareCard);
            }
        }
    });

})(window, _, Vue, GeckoClient);
