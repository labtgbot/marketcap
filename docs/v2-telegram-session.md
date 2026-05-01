# TONBANKCARD V2 Telegram Session Validation

Date: 2026-04-30

Issue: [#13](https://github.com/labtgbot/marketcap/issues/13)

## Objective

Trust Telegram Mini App identity only after the backend validates raw
`Telegram.WebApp.initData`. Browser-provided `initDataUnsafe` fields are not
trusted by the server.

## Endpoint

`POST /api/telegram/session` accepts a JSON body with `initData` or the
`X-Telegram-Init-Data` header:

```json
{
  "initData": "query_id=...&user=...&auth_date=...&hash=..."
}
```

The response uses the standard API envelope and sets an HttpOnly
`tonbankcard_session` cookie. The body intentionally contains only fields the
frontend needs for UI state:

```json
{
  "ok": true,
  "data": {
    "session": {
      "state": "telegram_validated",
      "source": "telegram_init_data",
      "surface": "telegram_mini_app",
      "expires_at": "2026-05-30T20:30:00Z"
    },
    "user": {
      "telegram_user_id": "123456789",
      "language_code": "en",
      "is_premium": true
    },
    "launch": {
      "start_param": "portfolio",
      "chat_type": "private",
      "chat_instance_present": true,
      "group_context": {
        "available": false,
        "scope": "personal",
        "chat_type": "private",
        "context_id": null,
        "personal_data_isolated": true
      }
    }
  }
}
```

Raw `initData`, hashes, query ids, chat instances, Telegram names, usernames,
and profile photos are not returned to the browser.

When Telegram provides a group-like `chat_type` and `chat_instance`, the
response derives a non-reversible `group_context.context_id` with a `tggrp_`
prefix from `chat_instance_hash`. This lets group-opened sessions show
group-specific context while keeping personal user data and raw group
identifiers isolated.

## Validation

The endpoint follows Telegram's server-side Mini App validation contract:

1. Parse raw `Telegram.WebApp.initData` as a query string.
2. Remove the received `hash` field.
3. Sort all remaining fields alphabetically.
4. Join them as `key=<value>` lines separated by `\n`.
5. Derive the secret key with HMAC-SHA-256 over the bot token using
   `WebAppData` as the key.
6. Compare the received hash with HMAC-SHA-256 of the data-check string using
   the derived secret key.
7. Reject stale `auth_date` values.

Reference: https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app

Validation requires:

- configured `TONBANKCARD_BOT_TOKEN`;
- valid `hash`;
- valid, fresh `auth_date`;
- valid JSON `user` object with a Telegram user id.

Tampered hashes return `invalid_telegram_init_data`. Stale auth data returns
`expired_telegram_init_data`. Signed data without a user returns
`missing_telegram_user`.

## Storage

Validated Telegram sessions are stored server-side:

- `users.telegram_user_id`, `telegram_language_code`, and
  `telegram_is_premium` hold the trusted Telegram identity needed for later bot
  and deletion workflows.
- `user_sessions.session_token_hash` stores only the server session token hash.
- `user_sessions.telegram_init_data_hash`, `start_param_hash`, and
  `chat_instance_hash` store hashes rather than raw launch data.
- `user_sessions.telegram_chat_type` stores the signed chat type when Telegram
  provides it.
- IP and user agent metadata are stored as hashes.

The `0002_telegram_session_context` migration adds the chat type column for
this endpoint while preserving the data minimization rules from issue #11.

## Browser Fallback

Local development can call `POST /api/telegram/session` without Telegram
`initData`. In the `local` profile only, the endpoint creates an anonymous
`local_browser` session so developers can exercise session-aware UI paths
without impersonating a production Telegram user.

Non-local profiles reject missing `initData` with `missing_init_data`.

## Tests

Regression coverage lives in `tests/telegram-session-check.sh` and runs through:

```sh
npm run test:telegram-session
```

The check covers valid signed `initData`, tampered hash rejection, stale auth
date rejection, signed data missing a Telegram user, local browser fallback,
production missing-data rejection, minimum response fields, and HttpOnly cookie
creation.

## Acceptance Criteria Mapping

| Issue #13 acceptance criterion | Coverage |
| --- | --- |
| Valid Telegram init data creates or refreshes a server session. | `/api/telegram/session`, MySQL/local storage helpers, and `tests/telegram-session-check.sh`. |
| Tampered hash, stale auth date, and missing required fields are rejected in tests. | Explicit tampered, stale, and missing-user test cases. |
| Frontend receives only the minimum session fields needed for UI. | Response payload excludes raw `initData`, query id, hash, chat instance, names, username, and photo URL. |
| Browser fallback works for local development without impersonating production users. | Local fallback returns an anonymous `local_browser` session; production missing-data requests are rejected. |
