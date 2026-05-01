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
                feedbackSubmitting: false
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
                    feature_disabled: 'Feature disabled',
                    schema_validation_failed: 'Safety validation blocked output',
                    unsafe_output_blocked: 'Safety validation blocked output'
                };
                const reason = this.result && this.result.reason ? this.result.reason : '';
                return labels[reason] || (reason ? _.startCase(reason) : 'Insight unavailable');
            },
            feedbackOptions: function () {
                return feedbackOptions;
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
                this.loading = true;

                GeckoClient.ai.insight(context)
                    .then(result => {
                        result = result || {};
                        if (result.insight) {
                            result.insight.provider = result.provider || null;
                            result.insight.prompt_version = result.prompt_version || null;
                            result.insight.request_id = result.request_id || null;
                        }
                        this.result = result;
                    })
                    .catch(() => {
                        this.result = {
                            status: 'insight unavailable',
                            reason: 'client_unavailable'
                        };
                    })
                    .finally(() => this.loading = false);
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
            }
        }
    });

})(window, _, Vue, GeckoClient);
