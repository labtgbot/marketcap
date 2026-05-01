(function (window, _, GeckoClient) {
    'use strict';

    const route = GeckoClient.routesConfig.achievements;
    if (!route) return;

    const options = GeckoClient.getOptions('achievements', {title: 'Achievements'});

    GeckoClient.router.addRoute({
        name: 'achievements',
        path: route.path,
        component: {
            template: '#route-achievements',
            data: function () {
                return {
                    snapshotData: this.emptySnapshot(),
                    achievementsUnsubscribe: null,
                    sharingId: '',
                    notice: ''
                };
            },
            created: function () {
                GeckoClient.setTitle(options.title || 'Achievements');
                this.initAchievements();
            },
            beforeDestroy: function () {
                if (this.achievementsUnsubscribe) this.achievementsUnsubscribe();
            },
            computed: {
                featureEnabled: function () {
                    return _.get(this.snapshotData, 'settings.enabled') === true;
                },
                optInModel: {
                    get: function () {
                        return this.snapshotData.opted_in === true;
                    },
                    set: function (value) {
                        this.toggleOptIn(value);
                    }
                },
                definitions: function () {
                    return this.snapshotData.definitions || [];
                },
                achievements: function () {
                    return this.snapshotData.achievements || {};
                },
                counters: function () {
                    return this.snapshotData.counters || {};
                },
                streak: function () {
                    return this.snapshotData.streak || {};
                },
                unlockedCount: function () {
                    return this.snapshotData.unlocked_count || 0;
                },
                activePrompts: function () {
                    return this.definitions.filter(definition => {
                        return _.get(this.achievements, [definition.id, 'prompt_state']) === 'active';
                    });
                },
                progressShareCard: function () {
                    return GeckoClient.achievements ? GeckoClient.achievements.progressShareCard() : null;
                }
            },
            methods: {
                emptySnapshot: function () {
                    return {
                        opted_in: false,
                        settings: {},
                        definitions: [],
                        achievements: {},
                        dismissed: {},
                        counters: {},
                        streak: {},
                        events: [],
                        unlocked_count: 0
                    };
                },
                initAchievements: function () {
                    if (!GeckoClient.achievements) return;

                    this.achievementsUnsubscribe = GeckoClient.achievements.onChange(snapshot => {
                        this.snapshotData = snapshot || this.emptySnapshot();
                    });

                    GeckoClient.achievements.init().then(() => {
                        this.snapshotData = GeckoClient.achievements.snapshot();
                    });
                },
                achievement: function (definition) {
                    return _.get(this.achievements, definition.id, null);
                },
                isUnlocked: function (definition) {
                    return !!this.achievement(definition);
                },
                badgeColor: function (definition) {
                    if (!this.isUnlocked(definition)) return 'grey';
                    if (definition.category === 'streak') return 'primary';
                    if (definition.category === 'ton_ecosystem') return 'deep-purple';
                    if (definition.category === 'sharing') return 'success';
                    if (definition.category === 'market_awareness') return 'warning';
                    return 'info';
                },
                progressValue: function (definition) {
                    if (this.isUnlocked(definition)) return 100;
                    const threshold = parseFloat(definition.threshold) || 1;
                    let value = 0;

                    if (definition.id === 'weekly_market_check') value = parseInt(this.streak.current_count, 10) || 0;
                    else if (definition.id === 'caught_market_movement') value = parseInt(this.counters.market_movement_caught, 10) || 0;
                    else value = parseInt(this.counters[definition.trigger], 10) || 0;

                    return Math.max(0, Math.min(100, Math.round((value / threshold) * 100)));
                },
                progressText: function (definition) {
                    if (this.isUnlocked(definition)) return 'Unlocked';
                    if (!this.featureEnabled) return 'Flag off';
                    if (!this.optInModel) return 'Opt-in';
                    if (definition.id === 'weekly_market_check') {
                        return (this.streak.current_count || 0) + '/' + definition.threshold + ' days';
                    }
                    if (definition.id === 'caught_market_movement') {
                        return (this.counters.market_movement_caught || 0) + '/1 move';
                    }
                    return (this.counters[definition.trigger] || 0) + '/' + definition.threshold;
                },
                toggleOptIn: function (enabled) {
                    if (!GeckoClient.achievements || !this.featureEnabled) return;
                    this.snapshotData = enabled ? GeckoClient.achievements.enable() : GeckoClient.achievements.disable();
                },
                dismissPrompt: function (definition) {
                    if (!GeckoClient.achievements) return;
                    this.snapshotData = GeckoClient.achievements.dismiss(definition.id);
                },
                shareAchievement: function (definition) {
                    if (!GeckoClient.achievements || !GeckoClient.share) return;
                    const card = GeckoClient.achievements.achievementShareCard(definition.id);
                    if (!card) return;

                    this.sharingId = definition.id;
                    GeckoClient.share.share(card)
                        .then(shared => {
                            if (shared) {
                                GeckoClient.achievements.markShared(definition.id);
                                this.notice = 'Achievement card ready';
                            }
                        })
                        .finally(() => this.sharingId = '');
                },
                shareProgress: function () {
                    if (!GeckoClient.share || !this.progressShareCard) return;
                    GeckoClient.share.share(this.progressShareCard);
                },
                unlockedLabel: function (definition) {
                    const achievement = this.achievement(definition);
                    if (!achievement) return 'Locked';
                    return 'Unlocked ' + this.relativeDate(achievement.unlocked_at);
                },
                relativeDate: function (timestamp) {
                    const date = new Date(timestamp);
                    if (!GeckoClient.utils.isValidDate(date)) return '';
                    return date.toISOString().slice(0, 10);
                }
            }
        }
    });

})(window, _, GeckoClient);
