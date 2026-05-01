# TONBANKCARD V2 Gamification Achievements

Date: 2026-05-01

Issue: [#34](https://github.com/labtgbot/marketcap/issues/34)

This document defines the Stage 4 achievement and streak layer for TONBANKCARD
V2. The feature is intentionally opt-in, non-blocking, and tied to useful
product behavior instead of invitation pressure.

## Product Contract

- Users can opt in from `/achievements`; core market, watchlist, alert, TON,
  and share workflows keep working when achievements are off.
- Achievement prompts are dismissible and never block navigation or form
  submission.
- Haptics are limited to successful unlock prompts in Telegram Mini App contexts
  that support Telegram `HapticFeedback`.
- The haptics setting is deployment-tunable and defaults to on for supported
  Telegram clients.
- Badges appear on the Achievements route and can produce shareable achievement
  cards through the existing Telegram `startapp` share card flow.
- The badges are local progress signals first; they do not gate market data,
  alerts, watchlists, TON views, or sharing.
- shareable achievement cards reuse the existing privacy-safe share card
  contract.
- Admin controls use `TONBANKCARD_FEATURE_GAMIFICATION=true` plus tunable
  environment defaults exposed by `/api/achievements/settings`.

## Achievement Definitions

| ID | Trigger | User value | Default threshold |
| --- | --- | --- | --- |
| `first_watchlist` | `watchlist_added` | Save the first useful market asset. | 1 add |
| `first_alert` | `alert_created` | Create the first smart alert rule. | 1 rule |
| `weekly_market_check` | `market_check` | Build a market-check streak across local dates. | `TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS` days |
| `ton_explorer` | `ton_viewed` | Open TON ecosystem market context. | 1 view |
| `share_milestone` | `share_started` | Share useful market context. | `TONBANKCARD_ACHIEVEMENT_SHARE_MILESTONE_COUNT` shares |
| `caught_market_movement` | `market_movement_caught` | Notice a qualifying 24h market movement. | `TONBANKCARD_ACHIEVEMENT_MOVEMENT_THRESHOLD_PERCENT` absolute move |

## Streak Rules

- Streaks use local calendar dates from the user's browser timezone.
- Multiple market checks on the same local date count once.
- UTC boundary cases are tested in `tests/achievements-check.sh` with
  `America/New_York` and `Asia/Tokyo`.
- The browser stores local streak state in `TONBANKCARD:achievements:v1`; the
  durable schema in `0009_gamification_achievements` is ready for trusted
  Telegram sync later.

## Admin Controls

| Control | Default | Range | Purpose |
| --- | --- | --- | --- |
| `TONBANKCARD_FEATURE_GAMIFICATION` | `false` | boolean | Global enable or disable. |
| `TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS` | `7` | 2-30 | Consecutive local days for `weekly_market_check`. |
| `TONBANKCARD_ACHIEVEMENT_SHARE_MILESTONE_COUNT` | `3` | 1-100 | Shares required for `share_milestone`. |
| `TONBANKCARD_ACHIEVEMENT_MOVEMENT_THRESHOLD_PERCENT` | `7.5` | 1-100 | Absolute 24h move for `caught_market_movement`. |
| `TONBANKCARD_ACHIEVEMENT_MAX_PROMPTS_PER_SESSION` | `1` | 1-6 | Active achievement prompts shown at once. |

The public settings endpoint exposes only safe values and does not expose
provider keys, bot tokens, database credentials, or admin secrets.
These admin controls can be changed without forcing users to invite friends or
blocking core workflows.

## Analytics Events

Achievement events appear in analytics through the allowlisted browser helper:

- `achievement_opted_in`
- `achievement_opted_out`
- `achievement_streak_updated`
- `achievement_prompted`
- `achievement_unlocked`
- `achievement_dismissed`
- `achievement_shared`

Allowed achievement properties are coarse values: `achievement_id`,
`achievement_category`, `achievement_count`, `streak_count`,
`streak_timezone`, `prompt_state`, `haptic_type`, `movement_bucket`,
`source_route`, `coin_id`, and `symbol`.

## Files and Checks

- API: `api/achievements.php`, `/api/achievements/settings`
- Frontend service: `dev/js/src/achievements.js`
- Route: `templates/routes/achievements.php` and
  `dev/js/src/routes/achievements.js`
- Migration: `database/migrations/0009_gamification_achievements.up.sql`
- Regression check: `tests/achievements-check.sh`

Run the focused check with:

```sh
npm run test:achievements
```

## Acceptance Criteria Mapping

| Issue #34 acceptance criterion | Coverage |
| --- | --- |
| Achievements are opt-in or low-friction and do not force invitations. | `/achievements` opt-in switch, local-only tracking while off, and no invite requirement. |
| Streak calculations are tested across time zones. | `tests/achievements-check.sh` verifies New York and Tokyo local-date streaks. |
| Users can dismiss achievement prompts. | Prompt state supports `active`, `queued`, and `dismissed`; dismiss emits analytics. |
| Achievement events appear in analytics. | `dev/js/src/initial.js` allowlists unlock, prompt, dismiss, share, opt-in, opt-out, and streak events. |
