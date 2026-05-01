(function (_, Vue, GeckoClient) {
    'use strict';

    Vue.component('gc-share-card', {
        template: '#component-share-card',
        props: {
            card: {
                type: Object,
                default: null
            },
            dense: {
                type: Boolean,
                default: false
            }
        },
        data: function () {
            return {
                sharing: false,
                copied: false
            };
        },
        computed: {
            normalizedCard: function () {
                if (!GeckoClient.share) return this.card || {};
                return GeckoClient.share.normalizeCard(this.card || {});
            },
            metrics: function () {
                return _.isArray(this.normalizedCard.metrics) ? this.normalizedCard.metrics : [];
            },
            shareLabel: function () {
                return 'Share ' + (this.normalizedCard.title || 'market card');
            }
        },
        methods: {
            shareCard: function () {
                if (!GeckoClient.share || this.sharing) return;

                this.sharing = true;
                this.copied = false;
                GeckoClient.share.share(this.normalizedCard)
                    .then(shared => {
                        this.copied = shared === true;
                        this.$emit('shared', this.normalizedCard);
                    })
                    .finally(() => this.sharing = false);
            }
        }
    });

})(_, Vue, GeckoClient);
