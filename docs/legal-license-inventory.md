# Legal License Inventory

Date: 2026-04-30

This inventory records the legal baseline observed in the extracted Gecko Client
archive and the current repository tree for TONBANKCARD Crypto Tracker. It is a
working inventory for release preparation, not a legal opinion.

## Repository Notice

The root `NOTICE` file attributes TONBANKCARD modifications and points to this
inventory. Future V2 changes must keep the root notice current when new
third-party code, source assets, generated bundles, or replacement brand assets
are added.

## Gecko Client Baseline

| Area | Paths | Observed notice | Preservation rule |
| --- | --- | --- | --- |
| Original archive | GitHub Release artifact or approved artifact-store object; not tracked in this repository | Extracted Gecko Client project archive with 2021 file timestamps. | Keep any required audit copy outside the source tree with checksum/provenance notes. Do not commit ZIP archives to this repository. |
| Gecko Client PHP and tool sources | `functions.php`, `index.php`, `vendor.php`, `constants.php`, `config/*.php`, `views/*.php`, `templates/**/*.php`, `dev/js/tools/*` | File headers identify `Gecko Client`, RunCoders, `Envato Market Regular License`, and Copyright (c) 2021 RunCoders. | Do not remove file headers when editing these files. New V2 code should add TONBANKCARD attribution separately instead of replacing upstream notices. |
| Gecko Client browser modules | `dev/js/src/**/*.js` | Extracted archive source modules do not all carry individual file headers. They remain part of the Gecko Client source baseline. | Preserve upstream provenance through this inventory and generated bundle records. Add notices if these modules are copied into new standalone files. |
| Generated bundles | `assets/js/app.js`, `assets/js/app.min.js` | Bundled application output generated from Gecko Client JavaScript sources and local vendor/runtime code. | Generated bundles must not be treated as notice-free output. When rebundled, preserve available banners or provide equivalent notice coverage in `NOTICE` and this inventory. |

## Vendored Packages

| Package | Paths | Version or observed marker | Observed notice or license signal | Release action |
| --- | --- | --- | --- | --- |
| Axios | `assets/vendor/axios/*` | `axios v0.21.1` in `axios.js` and `axios.min.js` banners | Copyright banner for Matt Zabriskie. No standalone license file is bundled in the extracted archive. | Verify the upstream package license before public launch and add a bundled license file if required. |
| ECharts | `assets/vendor/echarts/*` | ECharts distribution files | Apache License 2.0 header in `echarts.js` and `echarts.min.js`; bundled third-party notices appear inside `echarts.js`. | Preserve Apache headers and embedded notices when replacing or minifying. |
| Lodash | `assets/vendor/lodash/*` | `VERSION = '4.17.21'` in `lodash.js` | MIT license header in `lodash.js` and `lodash.min.js`, including OpenJS Foundation and Underscore.js attribution. | Preserve the license banner in all distributed copies. |
| Material Design Icons | `assets/vendor/mdi/css/*`, `assets/vendor/mdi/fonts/*` | CSS references Material Design Icons webfont `v=5.9.55` | No standalone license file is bundled in the extracted archive. | Verify the upstream package license before public launch and add a bundled license file if required. |
| Roboto fonts | `assets/vendor/roboto/*` | Roboto font files and CSS | `assets/vendor/roboto/fonts/LICENSE.txt` contains Apache License 2.0; `COPYRIGHT.txt` records Google copyright. | Preserve the Roboto license and copyright files with the font files. |
| Vue | `assets/vendor/vue/*` | Vue.js `v2.6.14` | MIT license header in `vue.js` and `vue.min.js`. | Preserve the MIT banner in distributed copies. |
| Vue Router | `assets/vendor/vue-router/*` | Vue Router `v3.5.2` | MIT license header in `vue-router.js` and `vue-router.min.js`. | Preserve the MIT banner in distributed copies. |
| Vuetify | `assets/vendor/vuetify/*` | Vuetify `v2.5.8` | MIT license banner in `vuetify.min.js` and `vuetify.min.css`; `vuetify.js` is bundled without the same top-level banner in this archive. | Preserve existing banners and verify full package license coverage before public launch. |

## Image Assets

| Asset group | Paths | Observed provenance | Release action |
| --- | --- | --- | --- |
| App icons and logo | `assets/images/favicon.ico`, `assets/images/favicon-*.png`, `assets/images/apple-touch-icon.png`, `assets/images/android-chrome-192x192.png`, `assets/images/logo.png` | Extracted from `gecko-client.zip`; no standalone image license file is present. | Treat as Gecko Client placeholder assets until approved TONBANKCARD replacements land. Preserve provenance while they remain in the tree. |
| TONBANKCARD brand assets | `assets/images/tonbankcard-icon.svg`, `assets/images/tonbankcard-logo.svg`, `assets/images/tonbankcard-icon-*.png`, `assets/images/tonbankcard-apple-touch-icon.png` | Created for issue #7 as TONBANKCARD Crypto Tracker replacement logo and icon assets using simple geometric SVG/PNG artwork. | Use for the public site logo, favicon, browser icons, and Apple touch icon. Keep the asset names and provenance in this inventory when refining the brand kit. |
| Team photos | `assets/images/team/*.jpg` | Extracted from `gecko-client.zip`; no standalone image license file is present. | Treat as Gecko Client placeholder assets and replace or verify rights before public launch. |

## Preservation Requirements For Future V2 Work

- Keep original Gecko Client source headers intact in source, templates, config,
  assets, and generated bundles.
- Keep existing vendor banners and standalone license files when rebuilding,
  minifying, or replacing vendor assets.
- Add a new inventory row before adding a new third-party package, CDN asset,
  model-generated image, font, icon set, or externally sourced dataset.
- When replacing Gecko Client images or UI assets with TONBANKCARD assets, record
  the asset source, owner, approval status, and license terms in this inventory.
- Run `sh tests/legal-baseline-check.sh` before every release candidate.
