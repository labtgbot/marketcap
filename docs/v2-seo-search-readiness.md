# TONBANKCARD V2 Search Engine Readiness

Issue: [#157](https://github.com/labtgbot/marketcap/issues/157)

The public website renders crawler-readable metadata from `config/routes-v2.php`
and `functions.php` before the Vue app hydrates. This keeps Google, Yandex, and
other search engines from depending on client-side route changes for core SEO
signals.

## Public Metadata

- Each indexable public route gets a server-rendered title, description,
  canonical URL, robots policy, Open Graph/Twitter metadata, and JSON-LD.
- Legacy coin URLs such as `/currency/bitcoin` render but canonicalize to
  `/coins/bitcoin`, so crawlers do not see duplicate coin detail pages.
- Private or account-adjacent surfaces such as `/settings` use `noindex,nofollow`
  and are excluded from the sitemap.

## Robots And Sitemap

- `/robots.txt` allows the public site, blocks operational paths such as
  `/admin/`, `/api/`, `/database/`, `/dev/`, and `/install/`, and exposes the
  absolute `/sitemap.xml` URL.
- Yandex receives a `Clean-param` rule for common tracking parameters
  (`utm_*`, `yclid`, `gclid`, and `fbclid`) so campaign links do not create
  duplicate indexed URLs.
- `/sitemap.xml` is generated from indexable public routes, omits dynamic
  placeholders, excludes legacy aliases and private settings pages, and emits a
  `lastmod` date for every URL. Route `lastmod` values are derived from the
  route metadata and route template file modification times unless a route
  defines `sitemap_lastmod` explicitly.

## Verification

Run the focused SEO check after SEO route changes:

```sh
npm run test:seo
```

The broader public shell check also verifies the shared crawler metadata:

```sh
npm run test:public-shell
```

## References

- Google Search Central: `robots.txt` sitemap field support and fully qualified
  sitemap URLs: https://developers.google.com/search/reference/robots_txt
- Yandex Webmaster: `robots.txt`, `Sitemap`, and `Clean-param` crawler controls:
  https://yandex.ru/support/webmaster/en/controlling-robot/robots-txt
