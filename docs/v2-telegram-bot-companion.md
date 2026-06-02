# TONBANKCARD V2 Telegram Bot Companion

Issue: [#36](https://github.com/labtgbot/marketcap/issues/36)

The Telegram bot companion turns bot commands, referrals, inline mode, and group launches into Mini App entry points. The webhook endpoint is `/api/telegram/bot`; `/api/telegram/bot/commands` returns the command lists that can be sent to Telegram with `setMyCommands`.

## Commands

Supported commands:

- `/start` opens the Mini App home route and accepts referral-aware `startapp` payloads.
- `/market` opens `/markets`.
- `/watchlist` opens `/watchlist`.
- `/alerts` opens `/alerts`.
- `/settings` opens `/settings`.
- `/support` opens `/support`.

Every command response is a Telegram `sendMessage` method payload with an inline button that opens a Telegram `startapp` Mini App URL when `TONBANKCARD_BOT_USERNAME` is configured. The generated payload uses the existing share/referral builder so campaign, context, and inviter attribution stay consistent with shareable market cards. A `/start s_...` referral payload is preserved instead of being rebuilt.

English and Russian command copy is available. Russian users are detected from `language_code=ru` and receive Russian bot copy for commands and inline mode.

## Inline Mode

Inline mode returns shareable `article` cards through an `answerInlineQuery` method payload. Results include:

- A market overview card.
- A TON ecosystem card.
- Coin cards from the local search index, including curated TON entries.

Inline cards are not personal. Group inline results use a `telegram_group` context in their `startapp` payload, while private inline results use market or coin context. Inline message text includes the market disclaimer and a Mini App button so shared cards remain useful outside the bot chat.

## Group Flows

Group-opened Mini App sessions use Telegram `chat_type` and `chat_instance` from validated initData. The raw `chat_instance` is never returned to the browser. The session response exposes only:

- `available`, whether a group context can be derived.
- `scope`, `telegram_group` for group-like launches and `personal` otherwise.
- `chat_type`, the safe Telegram chat type.
- `context_id`, a bounded `tggrp_` prefix plus a short hash fragment.
- `personal_data_isolated`, always true for explicit privacy signaling.

Group command routes add `context=group` and do not mix personal watchlist, alert, or wallet data into the group launch context.

## Configuration

Relevant environment variables:

- `TONBANKCARD_BOT_USERNAME` builds `https://t.me/<bot>?startapp=...` links.
- `TONBANKCARD_BOT_TOKEN` validates Mini App initData in `/api/telegram/session`.
- `TONBANKCARD_BOT_WEBHOOK_SECRET` validates Telegram webhook requests through `X-Telegram-Bot-Api-Secret-Token`. **Mandatory for any webhook deployment.**

The webhook fails closed: when no secret is configured, every `/api/telegram/bot` request is rejected with `503 telegram_bot_secret_unconfigured`, so the endpoint can never accept unauthenticated updates out of the box. Configure `TONBANKCARD_BOT_WEBHOOK_SECRET` and set the same value in Telegram's webhook settings before exposing the webhook. Once configured, the header is compared with `hash_equals` and mismatches return `401 telegram_bot_unauthorized`.

## Errors And Logging

Malformed webhook updates return normal API error envelopes, for example `telegram_bot_invalid_update`, instead of Telegram method payloads. Bot webhook failures emit the `telegram_bot.webhook_error` event with `request_id`, status, and error code so operational logs can be correlated with the request and Telegram delivery attempts.

## Regression Coverage

Run:

```sh
npm run test:telegram-bot
```

The check covers command links, referral-aware `/start`, inline mode market and coin cards, group `chat_instance` isolation in Mini App sessions, Russian copy, and request-id logging for bot errors.
