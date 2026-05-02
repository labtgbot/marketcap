#!/usr/bin/env sh
set -eu

failures=0
doc=docs/v2-launch-readiness.md
checklist=docs/release-checklist.md

fail() {
    printf '%s\n' "FAIL: $1" >&2
    failures=$((failures + 1))
}

assert_file() {
    if [ ! -f "$1" ]; then
        fail "Missing required file: $1"
    fi
}

assert_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Cannot inspect missing file: $file"
        return
    fi

    if ! grep -Eq -- "$pattern" "$file"; then
        fail "$file does not document $description"
    fi
}

assert_json_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Cannot inspect missing file: $file"
        return
    fi

    if ! grep -Eq -- "$pattern" "$file"; then
        fail "$file does not include $description"
    fi
}

assert_file "$doc"
assert_file "$checklist"
assert_file site.webmanifest
assert_file docs/screenshots/issue-20-public-shell-mobile.png
assert_file docs/screenshots/issue-26-pwa-telegram-webview.png
assert_file docs/screenshots/issue-32-alerts.png
assert_file docs/screenshots/issue-37-premium.png

assert_contains "$doc" '^# TONBANKCARD V2 Launch Readiness$' 'the launch-readiness title'
assert_contains "$doc" 'Issue: \[#40\]' 'the issue reference'
assert_contains "$doc" 'BotFather Main Mini App' 'BotFather Main Mini App setup'
assert_contains "$doc" 'Configure Mini App' 'BotFather Configure Mini App path'
assert_contains "$doc" 'Configure Splash Screen' 'loading screen configuration path'
assert_contains "$doc" 'localized preview media' 'localized screenshots or videos'
assert_contains "$doc" 'English' 'English localized media guidance'
assert_contains "$doc" 'Russian' 'Russian localized media guidance'
assert_contains "$doc" 'docs/screenshots/issue-26-pwa-telegram-webview\.png' 'Telegram Mini App screenshot evidence'
assert_contains "$doc" 'marketcap\.tonbankcard\.com' 'the production domain'
assert_contains "$doc" 'SSL' 'SSL verification'
assert_contains "$doc" 'DNS' 'DNS verification'
assert_contains "$doc" '/api/health' 'API health verification'
assert_contains "$doc" '/api/ready' 'API readiness verification'
assert_contains "$doc" 'Upstash Redis' 'cache verification'
assert_contains "$doc" 'MySQL' 'database verification'
assert_contains "$doc" 'cron' 'cron or scheduled job verification'
assert_contains "$doc" 'worker' 'worker verification'
assert_contains "$doc" 'secrets' 'secret verification'
assert_contains "$doc" 'backup' 'backup verification'
assert_contains "$doc" 'rollback' 'rollback path documentation'
assert_contains "$doc" 'incident response' 'incident response path documentation'
assert_contains "$doc" 'support workflow' 'support workflow documentation'
assert_contains "$doc" 'admin runbook' 'admin runbook documentation'
assert_contains "$doc" 'internal test' 'internal test rollout phase'
assert_contains "$doc" 'beta' 'beta rollout phase'
assert_contains "$doc" 'public launch' 'public launch rollout phase'
assert_contains "$doc" 'Owner' 'assigned owners'
assert_contains "$doc" 'Evidence' 'evidence capture requirements'

assert_contains "$checklist" 'Launch owner matrix' 'assigned launch owner matrix'
assert_contains "$checklist" 'BotFather Main Mini App' 'Mini App profile launch checklist'
assert_contains "$checklist" 'localized preview media' 'localized Mini App media checklist'
assert_contains "$checklist" 'Production verification matrix' 'production verification matrix'
assert_contains "$checklist" 'Rollback and incident response' 'rollback and incident response checklist'
assert_contains "$checklist" 'marketcap\.tonbankcard\.com' 'production domain checklist'
assert_contains "$checklist" 'Owner' 'assigned checklist owners'

assert_contains README.md 'docs/v2-launch-readiness\.md' 'the launch readiness documentation link'
assert_contains README.md 'npm run test:launch-readiness' 'the launch readiness npm check'
assert_contains package.json '"test:launch-readiness"' 'the launch readiness npm script'
assert_contains package.json 'tests/launch-readiness-check\.sh' 'the launch readiness shell check'
assert_contains package.json 'test:launch-readiness' 'the aggregate launch readiness check'

assert_json_contains site.webmanifest '"name"[[:space:]]*:[[:space:]]*"TONBANKCARD Crypto Tracker"' 'the Mini App/PWA display name'
assert_json_contains site.webmanifest '"background_color"[[:space:]]*:[[:space:]]*"#0B1020"' 'the documented dark loading background color'
assert_json_contains site.webmanifest '"theme_color"[[:space:]]*:[[:space:]]*"#0B1020"' 'the documented dark theme color'
assert_json_contains site.webmanifest 'tonbankcard-icon-512x512\.png' 'the high-resolution app icon'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Launch readiness check passed.'
