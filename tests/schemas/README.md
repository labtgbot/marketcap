# Bundled sitemap schemas

These are verbatim, offline copies of the official **sitemaps.org 0.9** XML
Schemas, vendored so `tests/sitemap-coverage-check.sh` can validate the
generated sitemap against the real schema without making any network request
(keeping the CI check fully deterministic — issue #169).

| File | Source |
| --- | --- |
| `sitemap.xsd` | <https://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd> |
| `siteindex.xsd` | <https://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd> |

Do not edit these files — they must stay byte-for-byte identical to the
published schemas so validation reflects what crawlers enforce.

## Note on the hreflang (`xhtml:link`) extension

Our sitemaps emit Google's hreflang alternates as `<xhtml:link>` elements
**immediately after `<loc>`**. The strict 0.9 schema only allows
foreign-namespace elements via an `<xsd:any>` wildcard at the *end* of the
`url` sequence, so a document with inline `xhtml:link` does not validate
against the unmodified schema. The coverage check therefore strips
foreign-namespace nodes before XSD validation (validating the core structure)
and asserts the hreflang alternates separately with explicit pattern checks.
