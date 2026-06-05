# TONBANKCARD Runtime Configuration

Issue: [#5](https://github.com/labtgbot/marketcap/issues/5)

The app now reads deployment settings from environment variables through
`config/runtime.php`. A local `.env` file is supported for development and is
ignored by Git. Copy `.env.example` to `.env` when you want file-based local
settings, or set the same variables in the web server, process manager, or
deployment secret store.

## Profiles

`TONBANKCARD_PROFILE` supports local, staging, production, and telegram
profiles.

| Profile | Purpose | Active base URL |
| --- | --- | --- |
| `local` | Fresh checkout and local PHP server. | `TONBANKCARD_LOCAL_BASE_URL`, default `http://localhost:8888/`. |
| `staging` | Public staging deployment. | `TONBANKCARD_STAGING_BASE_URL`. |
| `production` | Production public website. | `TONBANKCARD_PUBLIC_BASE_URL`. |
| `telegram` | Telegram Mini App deployment. | `TONBANKCARD_TELEGRAM_BASE_URL`. |

`TONBANKCARD_PUBLIC_BASE_URL` and `TONBANKCARD_TELEGRAM_BASE_URL` are separate
values. This lets public website URLs and Telegram Mini App URLs coexist without
editing source files.

## Required Variables

Local development has safe defaults so a fresh checkout can run with:

```sh
php -S localhost:8888
```

For staging, production, and telegram profiles, set explicit values for:

| Variable | Required when | Notes |
| --- | --- | --- |
| `TONBANKCARD_PROFILE` | Always | `local`, `staging`, `production`, or `telegram`. |
| `TONBANKCARD_LOCAL_BASE_URL` | Local overrides | Defaults to `http://localhost:8888/`. |
| `TONBANKCARD_STAGING_BASE_URL` | `staging` profile | Absolute HTTPS URL. |
| `TONBANKCARD_PUBLIC_BASE_URL` | `production` and `telegram` profiles | HTTPS public website URL for canonical and shared links. |
| `TONBANKCARD_TELEGRAM_BASE_URL` | `telegram` profile | HTTPS Mini App URL configured in BotFather. |
| `TONBANKCARD_STATE_DIR` | Optional | Private directory for local JSON state. Defaults to `.tonbankcard-marketcap-state` beside the app directory, outside the web root. |
| `TONBANKCARD_BOT_USERNAME` | Non-local profiles | Username only, without secret token. |
| `TONBANKCARD_BOT_TOKEN` | `telegram` profile or alerts enabled | Secret; server-side only. |
| `COINGECKO_API_PLAN` | Optional | `demo` for CoinGecko Public/Demo API or `pro` for CoinGecko Pro API. Defaults to `demo`. |
| `COINGECKO_API_KEY` | `COINGECKO_API_PLAN=pro` | Secret; server-side only. Optional for `demo`. |
| `GROQ_API_KEY` | AI feature enabled | Secret; server-side only. |
| `UPSTASH_REDIS_REST_URL` | Non-local profiles | Cache/rate-limit endpoint. |
| `UPSTASH_REDIS_REST_TOKEN` | Non-local profiles | Secret; server-side only. |
| `MYSQL_DSN` | Non-local profiles | Example: `mysql:host=127.0.0.1;dbname=marketcap;charset=utf8mb4`. |
| `MYSQL_USER` | Non-local profiles | Database application user. |
| `MYSQL_PASSWORD` | Non-local profiles | Secret; server-side only. |
| `MYSQL_SSL_CA` | Non-local profiles | CA certificate file path passed to `PDO::MYSQL_ATTR_SSL_CA` so MySQL/MariaDB connections request TLS. |
| `MYSQL_SSL_VERIFY_SERVER_CERT` | Non-local profiles | Defaults to `true`; passed to `PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT` to verify the database server certificate. |
| `MYSQL_SSL_CERT`, `MYSQL_SSL_KEY`, `MYSQL_SSL_CAPATH`, `MYSQL_SSL_CIPHER` | Optional | Advanced `PDO::MYSQL_ATTR_SSL_*` client certificate, CA directory, and cipher options when required by the database provider. |
| `CHANGENOW_LINK_ID` | ChangeNOW feature enabled | Partner link id, for example `f300d9f2b6f88e`. |

Set explicit production feature flags with `true` or `false`:

- `TONBANKCARD_FEATURE_AI`
- `TONBANKCARD_FEATURE_ALERTS`
- `TONBANKCARD_FEATURE_CHANGENOW`
- `TONBANKCARD_FEATURE_TON_CONNECT`
- `TONBANKCARD_FEATURE_REFERRALS`
- `TONBANKCARD_FEATURE_GAMIFICATION`
- `TONBANKCARD_FEATURE_PREMIUM`

The V2 market data gateway works by default without a CoinGecko API key. With
`COINGECKO_API_KEY` empty, `demo` uses the public CoinGecko API root and sends no
authentication header. Set `COINGECKO_API_KEY` only when more quota is needed;
`demo` sends it as `x-cg-demo-api-key` and `pro` uses the Pro API root with
`x-cg-pro-api-key`. The key is inserted only by the PHP backend and is never
included in `window.GeckoClient` or browser request parameters.

## Private JSON State

`TONBANKCARD_STATE_DIR` controls the default directory for sensitive local JSON
state: admin configuration (`TONBANKCARD_ADMIN_STORE`), local Telegram sessions
(`TONBANKCARD_LOCAL_SESSION_STORE`), AI feedback (`TONBANKCARD_AI_FEEDBACK_STORE`),
and TON manual curation (`TONBANKCARD_TON_CURATION_FILE`). When these store paths
are unset, the app writes files under `TONBANKCARD_STATE_DIR`.

The default state directory is outside `GECKO_CLIENT_DIR` and is created with
private permissions. The app refuses to read or write a sensitive JSON store when
the directory or existing file is group-readable, world-readable, group-writable,
world-writable, owned by another user, not writable by PHP, or a symlink. Use
directory mode `0700` and file mode `0600`.

Explicit temp-file overrides are for controlled local development only. Do not
point production, staging, or shared-host deployments at `/tmp` or another
world-readable/traversable directory; create an app-owned private directory outside
the web root instead.

## Transport Security

Non-local profiles enable HTTPS enforcement by default. `TONBANKCARD_FORCE_HTTPS`
redirects plain HTTP requests to the same host and URI over HTTPS, while localhost
and ACME challenges are exempt so local development and certificate issuance keep
working. Set `TONBANKCARD_FORCE_HTTPS=false` only behind an upstream component that
already enforces HTTPS.

`TONBANKCARD_HSTS_ENABLED` defaults to the HTTPS enforcement state for non-local
profiles and emits `Strict-Transport-Security: max-age=63072000;
includeSubDomains; preload` once runtime or request context confirms HTTPS.
Session cookies default to `Secure` whenever HTTPS enforcement is active or the
active base URL is HTTPS; `TONBANKCARD_SECURE_COOKIES` can be set explicitly for
special deployments.

## Observability

Operational logging defaults to warning-and-error records so failures are
traceable without noisy success logs:

- `TONBANKCARD_OBSERVABILITY_LOG_LEVEL`: defaults to `warning`; supports
  `debug`, `info`, `warning`, `error`, `critical`, and `off`.
- `TONBANKCARD_VERBOSE_TRACING`: defaults to `false`; when enabled, emits safe
  request/provider debug and info records with secrets redacted.
- `TONBANKCARD_CLIENT_ERROR_REPORTING`: defaults to `true`; allows browser boot,
  Vue, unhandled promise, and API errors to post to
  `/api/observability/client-error`.

See `docs/v2-observability-operational-logging.md` for the runbook and query
examples.

## AI Provider

The V2 AI provider layer is disabled by default through
`TONBANKCARD_FEATURE_AI=false`. When enabled, `/api/ai/insight` uses the
server-side Groq settings above, validates structured JSON before returning an
insight, and falls back to `insight unavailable` when the provider is missing,
rate-limited, unavailable, or returns unsafe output. Groq API keys and raw
prompts are not emitted in health responses, readiness responses, browser
configuration, or normal error payloads.

## Debug and Assets

Debug display defaults to on only for `local`. It defaults to off for staging,
production, and telegram. Override it with `TONBANKCARD_DEBUG=true` only during
controlled troubleshooting.

Asset behavior can be adjusted without source edits:

- `TONBANKCARD_APP_MINIFIED`: defaults to `false` locally and `true` elsewhere.
- `TONBANKCARD_PRECONNECT`: defaults to `true`.
- `TONBANKCARD_CDN`: defaults to `false` so bundled vendor assets remain the
  default.

## Failure Behavior

Missing production values fail with a configuration error page before the app
renders. Messages name the missing variable and show a safe example.

Secret values are never rendered in the configuration error payload or the
frontend `window.GeckoClient` object. The browser receives only non-secret
runtime metadata: profile, debug state, public URLs, bot username, and feature
flags.
