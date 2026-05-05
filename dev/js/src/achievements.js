(function (window, _, GeckoClient) {
    'use strict';

    const config = GeckoClient.achievementsConfig || {};
    const settings = normalizeSettings(_.get(config, 'settings', {}));
    const definitions = normalizeDefinitions(_.get(config, 'definitions', fallbackDefinitions(settings)));
    const definitionMap = _.keyBy(definitions, 'id');
    const changedEventName = 'tonbankcard:achievements-changed';
    let initialized = false;
    let state = emptyState();
    const listeners = [];

    function fallbackDefinitions(sourceSettings) {
        return [
            {id: 'first_watchlist', title: 'First watchlist', description: 'Save the first market asset to a watchlist.', trigger: 'watchlist_added', threshold: 1, category: 'useful_setup', badge_icon: 'mdi-star-check-outline'},
            {id: 'first_alert', title: 'First alert', description: 'Create the first smart alert rule.', trigger: 'alert_created', threshold: 1, category: 'useful_setup', badge_icon: 'mdi-bell-check-outline'},
            {id: 'weekly_market_check', title: 'Weekly market check', description: 'Check market pulse on consecutive local days.', trigger: 'market_check', threshold: sourceSettings.weekly_check_days, category: 'streak', badge_icon: 'mdi-calendar-week-outline'},
            {id: 'ton_explorer', title: 'TON explorer', description: 'Open TON ecosystem market context.', trigger: 'ton_viewed', threshold: 1, category: 'ton_ecosystem', badge_icon: 'mdi-diamond-stone'},
            {id: 'share_milestone', title: 'Share milestone', description: 'Share useful market context.', trigger: 'share_started', threshold: sourceSettings.share_milestone_count, category: 'sharing', badge_icon: 'mdi-share-variant-outline'},
            {id: 'caught_market_movement', title: 'Caught market movement', description: 'Open a market view with a qualifying 24h move.', trigger: 'market_movement_caught', threshold: sourceSettings.movement_threshold_percent, category: 'market_awareness', badge_icon: 'mdi-trending-up'}
        ];
    }

    function normalizeSettings(source) {
        source = source || {};
        return {
            enabled: source.enabled === true,
            definitions_version: _.toString(source.definitions_version || 'v1'),
            storage_key: _.toString(source.storage_key || 'TONBANKCARD:achievements:v1'),
            weekly_check_days: clampInt(source.weekly_check_days, 7, 2, 30),
            share_milestone_count: clampInt(source.share_milestone_count, 3, 1, 100),
            movement_threshold_percent: clampNumber(source.movement_threshold_percent, 7.5, 1, 100),
            max_prompts_per_session: clampInt(source.max_prompts_per_session, 1, 1, 6),
            prompt_cooldown_hours: clampInt(source.prompt_cooldown_hours, 24, 1, 168),
            haptics_enabled: source.haptics_enabled !== false
        };
    }

    function normalizeDefinitions(items) {
        return (items || []).map(item => ({
            id: _.toString(item.id || '').trim(),
            title: _.toString(item.title || '').trim(),
            description: _.toString(item.description || '').trim(),
            trigger: _.toString(item.trigger || '').trim(),
            threshold: _.isNumber(item.threshold) ? item.threshold : parseFloat(item.threshold) || 1,
            category: _.toString(item.category || 'progress').trim(),
            badge_icon: _.toString(item.badge_icon || 'mdi-trophy-outline').trim()
        })).filter(item => item.id && item.title);
    }

    function clampInt(value, fallback, min, max) {
        value = parseInt(value, 10);
        if (!_.isFinite(value)) value = fallback;
        return Math.max(min, Math.min(max, value));
    }

    function clampNumber(value, fallback, min, max) {
        value = parseFloat(value);
        if (!_.isFinite(value)) value = fallback;
        return Math.max(min, Math.min(max, value));
    }

    function canUseLocalStorage() {
        try {
            const key = '__tonbankcard_achievements_test__';
            window.localStorage.setItem(key, '1');
            window.localStorage.removeItem(key);
            return true;
        } catch (err) {
            return false;
        }
    }

    function nowIso() {
        return new Date().toISOString();
    }

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function userTimezone() {
        return _.get(Intl.DateTimeFormat().resolvedOptions(), 'timeZone') || 'UTC';
    }

    function emptyState() {
        return {
            version: 1,
            opted_in: false,
            achievements: {},
            dismissed: {},
            counters: {},
            market_check_dates: [],
            streak: {
                timezone: userTimezone(),
                current_count: 0,
                max_count: 0,
                last_local_date: null
            },
            events: [],
            updated_at: null
        };
    }

    function normalizeState(raw) {
        const normalized = Object.assign(emptyState(), _.isPlainObject(raw) ? raw : {});
        normalized.achievements = _.isPlainObject(normalized.achievements) ? normalized.achievements : {};
        normalized.dismissed = _.isPlainObject(normalized.dismissed) ? normalized.dismissed : {};
        normalized.counters = _.isPlainObject(normalized.counters) ? normalized.counters : {};
        normalized.market_check_dates = _.isArray(normalized.market_check_dates) ? normalized.market_check_dates : [];
        normalized.events = _.isArray(normalized.events) ? normalized.events : [];
        normalized.streak = Object.assign(emptyState().streak, _.isPlainObject(normalized.streak) ? normalized.streak : {});
        return normalized;
    }

    function loadStoredState() {
        if (!canUseLocalStorage()) return emptyState();

        try {
            const raw = window.localStorage.getItem(settings.storage_key);
            return normalizeState(raw ? JSON.parse(raw) : {});
        } catch (err) {
            return emptyState();
        }
    }

    function persistState() {
        state.updated_at = nowIso();
        if (!canUseLocalStorage()) return;

        try {
            window.localStorage.setItem(settings.storage_key, JSON.stringify(state));
        } catch (err) {}
    }

    function ensureInit() {
        if (initialized) return;
        state = loadStoredState();
        initialized = true;
    }

    function init() {
        ensureInit();
        notify();
        return Promise.resolve(GeckoClient.achievements);
    }

    function snapshot() {
        ensureInit();
        return clone({
            version: state.version,
            opted_in: state.opted_in,
            feature_enabled: settings.enabled,
            settings: settings,
            definitions: definitions,
            achievements: state.achievements,
            dismissed: state.dismissed,
            counters: state.counters,
            market_check_dates: state.market_check_dates,
            streak: state.streak,
            events: state.events,
            unlocked_count: unlockedCount(),
            updated_at: state.updated_at
        });
    }

    function notify() {
        const detail = snapshot();
        listeners.slice().forEach(callback => callback(detail));

        if (typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent(changedEventName, {detail: detail}));
        }
    }

    function onChange(callback) {
        if (!_.isFunction(callback)) return function () {};
        listeners.push(callback);
        callback(snapshot());

        return function () {
            const index = listeners.indexOf(callback);
            if (index >= 0) listeners.splice(index, 1);
        };
    }

    function emitAnalytics(eventName, properties) {
        if (!GeckoClient.analytics) return null;
        return GeckoClient.analytics.emit(eventName, properties || {});
    }

    function hapticNotification(type) {
        if (!settings.haptics_enabled || !GeckoClient.telegram) return;
        if (_.isFunction(GeckoClient.telegram.hapticNotification)) {
            GeckoClient.telegram.hapticNotification(type || 'success');
        }
    }

    function enable() {
        ensureInit();
        if (state.opted_in) return snapshot();

        state.opted_in = true;
        persistState();
        emitAnalytics('achievement_opted_in', {prompt_state: 'enabled'});
        notify();
        return snapshot();
    }

    function disable() {
        ensureInit();
        if (!state.opted_in) return snapshot();

        state.opted_in = false;
        persistState();
        emitAnalytics('achievement_opted_out', {prompt_state: 'disabled'});
        notify();
        return snapshot();
    }

    function canTrack() {
        ensureInit();
        return settings.enabled === true && state.opted_in === true;
    }

    function incrementCounter(name, amount) {
        state.counters[name] = (parseInt(state.counters[name], 10) || 0) + (amount || 1);
        return state.counters[name];
    }

    function recordEvent(eventName, properties) {
        state.events.unshift({
            event_name: eventName,
            occurred_at: _.toString(properties.occurred_at || nowIso()),
            coin_id: safeString(properties.coin_id, 96),
            symbol: safeString(properties.symbol, 32),
            source_route: safeString(properties.source_route || properties.sourceRoute, 80)
        });
        state.events = state.events.slice(0, 80);
    }

    function track(eventName, properties) {
        properties = properties || {};
        ensureInit();
        if (!canTrack()) return [];

        const occurredAt = properties.occurred_at || nowIso();
        recordEvent(eventName, Object.assign({}, properties, {occurred_at: occurredAt}));

        if (eventName === 'market_check') {
            updateStreak(occurredAt, properties.timezone || userTimezone());
            emitAnalytics('achievement_streak_updated', {
                streak_count: state.streak.current_count,
                streak_timezone: state.streak.timezone,
                source_route: properties.source_route || properties.sourceRoute || null
            });
        } else {
            incrementCounter(eventName, 1);
        }

        if (eventName === 'market_movement_caught' && !state.counters.market_movement_caught) {
            state.counters.market_movement_caught = 1;
        }

        const unlocked = evaluateAchievements(eventName, properties);
        persistState();
        notify();
        return unlocked;
    }

    function evaluateAchievements(eventName, properties) {
        return definitions
            .map(definition => {
                if (state.achievements[definition.id]) return null;
                if (!achievementSatisfied(definition)) return null;
                return unlockAchievement(definition, eventName, properties);
            })
            .filter(Boolean);
    }

    function achievementSatisfied(definition) {
        if (definition.id === 'weekly_market_check') {
            return (parseInt(state.streak.current_count, 10) || 0) >= settings.weekly_check_days;
        }

        if (definition.id === 'caught_market_movement') {
            return (parseInt(state.counters.market_movement_caught, 10) || 0) >= 1;
        }

        return (parseInt(state.counters[definition.trigger], 10) || 0) >= (parseFloat(definition.threshold) || 1);
    }

    function unlockAchievement(definition, eventName, properties) {
        const activePromptCount = activePrompts().length;
        const promptState = activePromptCount < settings.max_prompts_per_session ? 'active' : 'queued';
        const achievement = {
            id: definition.id,
            unlocked_at: nowIso(),
            event_name: eventName,
            category: definition.category,
            prompt_state: promptState,
            dismissed_at: null,
            shared_at: null
        };

        state.achievements[definition.id] = achievement;
        emitAnalytics('achievement_unlocked', {
            achievement_id: definition.id,
            achievement_category: definition.category,
            achievement_count: unlockedCount(),
            streak_count: state.streak.current_count,
            streak_timezone: state.streak.timezone,
            prompt_state: promptState,
            haptic_type: settings.haptics_enabled ? 'success' : 'off',
            movement_bucket: properties.movement_bucket || null,
            source_route: properties.source_route || properties.sourceRoute || null,
            coin_id: properties.coin_id || null,
            symbol: properties.symbol || null
        });

        if (promptState === 'active') {
            emitPrompted(definition);
            hapticNotification('success');
        }

        return achievement;
    }

    function emitPrompted(definition) {
        emitAnalytics('achievement_prompted', {
            achievement_id: definition.id,
            achievement_category: definition.category,
            achievement_count: unlockedCount(),
            streak_count: state.streak.current_count,
            streak_timezone: state.streak.timezone,
            prompt_state: 'active'
        });
    }

    function activePrompts() {
        return _.values(state.achievements).filter(achievement => achievement.prompt_state === 'active');
    }

    function promoteQueuedPrompt() {
        if (activePrompts().length >= settings.max_prompts_per_session) return;
        const queued = _.find(_.values(state.achievements), achievement => achievement.prompt_state === 'queued');
        if (!queued) return;

        queued.prompt_state = 'active';
        const definition = definitionMap[queued.id];
        if (definition) {
            emitPrompted(definition);
            hapticNotification('success');
        }
    }

    function dismiss(id) {
        ensureInit();
        const achievement = state.achievements[id];
        if (!achievement) return snapshot();

        achievement.prompt_state = 'dismissed';
        achievement.dismissed_at = nowIso();
        state.dismissed[id] = achievement.dismissed_at;
        const definition = definitionMap[id] || {id: id, category: 'progress'};
        emitAnalytics('achievement_dismissed', {
            achievement_id: id,
            achievement_category: definition.category,
            achievement_count: unlockedCount(),
            streak_count: state.streak.current_count,
            streak_timezone: state.streak.timezone,
            prompt_state: 'dismissed'
        });
        promoteQueuedPrompt();
        persistState();
        notify();
        return snapshot();
    }

    function markShared(id) {
        ensureInit();
        const achievement = state.achievements[id];
        if (!achievement) return snapshot();

        achievement.shared_at = nowIso();
        const definition = definitionMap[id] || {id: id, category: 'progress'};
        emitAnalytics('achievement_shared', {
            achievement_id: id,
            achievement_category: definition.category,
            achievement_count: unlockedCount(),
            streak_count: state.streak.current_count,
            streak_timezone: state.streak.timezone,
            share_context: 'achievement_card',
            campaign: 'achievement-' + id.replace(/_/g, '-'),
            route: '/achievements'
        });
        persistState();
        notify();
        return snapshot();
    }

    function localDate(value, timezone) {
        const date = new Date(value);
        if (!GeckoClient.utils.isValidDate(date)) return null;

        try {
            const parts = new Intl.DateTimeFormat('en-US', {
                timeZone: timezone || userTimezone(),
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).formatToParts(date).reduce((memo, part) => {
                memo[part.type] = part.value;
                return memo;
            }, {});

            return parts.year + '-' + parts.month + '-' + parts.day;
        } catch (err) {
            return date.toISOString().slice(0, 10);
        }
    }

    function updateStreak(timestamp, timezone) {
        timezone = timezone || userTimezone();
        const date = localDate(timestamp, timezone);
        if (!date) return state.streak;

        state.streak.timezone = timezone;
        if (state.market_check_dates.indexOf(date) === -1) {
            state.market_check_dates.push(date);
        }
        state.market_check_dates = _.uniq(state.market_check_dates).sort();
        state.streak = calculateStreak(state.market_check_dates, timezone);
        return state.streak;
    }

    function calculateStreak(dates, timezone) {
        dates = _.uniq((dates || []).filter(Boolean)).sort();
        let current = 0;
        let max = 0;
        let previous = null;

        dates.forEach(date => {
            if (!previous || dayDiff(previous, date) !== 1) current = 1;
            else current += 1;
            if (current > max) max = current;
            previous = date;
        });

        return {
            timezone: timezone || userTimezone(),
            current_count: dates.length ? current : 0,
            max_count: max,
            last_local_date: dates.length ? dates[dates.length - 1] : null
        };
    }

    function dayDiff(a, b) {
        const aParts = a.split('-').map(Number);
        const bParts = b.split('-').map(Number);
        const aTime = Date.UTC(aParts[0], aParts[1] - 1, aParts[2]);
        const bTime = Date.UTC(bParts[0], bParts[1] - 1, bParts[2]);
        return Math.round((bTime - aTime) / 86400000);
    }

    function trackRoute(to) {
        const name = _.get(to, 'name');
        const sourceRoute = name || _.get(to, 'path') || 'unknown';
        const paramId = _.toLower(_.get(to, 'params.id'));
        if ((name === 'currency' || name === 'coins') && (paramId === 'toncoin' || paramId === 'the-open-network')) {
            track('ton_viewed', {source_route: sourceRoute, coin_id: paramId, symbol: 'TON'});
        }
    }

    function trackMarketMovement(currency, sourceRoute) {
        const change = parseFloat(_.get(currency, 'price_change_percentage_24h_in_currency', _.get(currency, 'market.price_change_percentage_24h_in_currency')));
        if (!_.isFinite(change) || Math.abs(change) < settings.movement_threshold_percent) return [];

        return track('market_movement_caught', {
            coin_id: _.get(currency, 'id') || _.get(currency, 'coin_id') || null,
            symbol: _.get(currency, 'symbol') || null,
            source_route: sourceRoute || 'market_view',
            movement_bucket: movementBucket(change)
        });
    }

    function movementBucket(change) {
        const absolute = Math.abs(parseFloat(change) || 0);
        if (absolute < 5) return '1_5';
        if (absolute < 10) return '5_10';
        if (absolute < 25) return '10_25';
        return '25_plus';
    }

    function achievementShareCard(id) {
        ensureInit();
        const definition = definitionMap[id];
        if (!definition) return null;

        const achievement = state.achievements[id] || null;
        return {
            title: definition.title + ' badge',
            subtitle: achievement ? 'Unlocked achievement' : 'Achievement progress',
            body: definition.description,
            route: '/achievements',
            campaign: 'achievement-' + id.replace(/_/g, '-'),
            context: 'achievement_card',
            freshness: achievement ? 'Unlocked ' + localDate(achievement.unlocked_at, state.streak.timezone) : 'Progress saved locally',
            metrics: [
                {label: 'Badge', value: definition.title},
                {label: 'Category', value: _.startCase(definition.category)},
                {label: 'Streak', value: String(state.streak.current_count || 0)},
                {label: 'Unlocked', value: String(unlockedCount())}
            ]
        };
    }

    function progressShareCard() {
        ensureInit();
        return {
            title: 'Achievement progress',
            subtitle: unlockedCount() + ' of ' + definitions.length + ' badges',
            body: 'Opt-in TONBANKCARD market progress with streaks, useful setup badges, and shareable milestones.',
            route: '/achievements',
            campaign: 'achievement-progress',
            context: 'achievement_card',
            freshness: state.updated_at ? 'Updated ' + localDate(state.updated_at, state.streak.timezone) : 'Local progress',
            metrics: [
                {label: 'Unlocked', value: String(unlockedCount())},
                {label: 'Remaining', value: String(Math.max(0, definitions.length - unlockedCount()))},
                {label: 'Streak', value: String(state.streak.current_count || 0)},
                {label: 'Timezone', value: state.streak.timezone || userTimezone()}
            ]
        };
    }

    function unlockedCount() {
        return _.keys(state.achievements).length;
    }

    function safeString(value, maxLength) {
        value = _.toString(value || '').trim();
        return value ? value.slice(0, maxLength || 80) : null;
    }

    GeckoClient.achievements = {
        init: init,
        snapshot: snapshot,
        onChange: onChange,
        enable: enable,
        disable: disable,
        dismiss: dismiss,
        markShared: markShared,
        track: track,
        trackRoute: trackRoute,
        trackMarketMovement: trackMarketMovement,
        calculateStreak: calculateStreak,
        achievementShareCard: achievementShareCard,
        progressShareCard: progressShareCard
    };

})(window, _, GeckoClient);
