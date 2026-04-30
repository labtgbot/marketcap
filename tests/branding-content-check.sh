#!/usr/bin/env sh
set -eu

failures=0

fail() {
    printf '%s\n' "FAIL: $1" >&2
    failures=$((failures + 1))
}

assert_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Cannot inspect missing file: $file"
        return
    fi

    if ! grep -Eq "$pattern" "$file"; then
        fail "$file does not include $description"
    fi
}

assert_not_contains() {
    file=$1
    pattern=$2
    description=$3

    if [ ! -f "$file" ]; then
        fail "Cannot inspect missing file: $file"
        return
    fi

    if grep -Eq "$pattern" "$file"; then
        fail "$file still includes $description"
    fi
}

assert_contains config/site.php "\\\$site\\['name'\\] = 'TONBANKCARD';" 'the TONBANKCARD product name'
assert_contains config/site.php 'TONBANKCARD Crypto Tracker' 'the TONBANKCARD market product title'
assert_contains config/site.php 'tonbankcard-logo\.svg' 'TONBANKCARD logo asset usage'
assert_contains config/site.php 'tonbankcard-icon\.svg' 'TONBANKCARD icon asset usage'
assert_not_contains config/site.php "\\\$site\\['name'\\] = 'Gecko Client';" 'the Gecko Client visible site name'

assert_contains config/navigation.php 'https://t\.me/tonbankcard' 'approved TONBANKCARD Telegram channel link'
assert_contains config/navigation.php 'https://t\.me/tonbankcard_ru' 'approved TONBANKCARD Telegram RU channel link'
assert_contains config/navigation.php 'https://t\.me/tonbankcard_chat' 'approved TONBANKCARD Telegram chat link'
assert_contains config/navigation.php 'https://t\.me/tonbankcard_chat_ru' 'approved TONBANKCARD Telegram RU chat link'
assert_contains config/navigation.php 'https://twitter\.com/tonbankcard' 'approved TONBANKCARD X/Twitter link'
assert_contains config/navigation.php 'https://vk\.com/tonbankcard' 'approved TONBANKCARD VK link'
assert_contains config/navigation.php 'https://www\.youtube\.com/@tonbankcard' 'approved TONBANKCARD YouTube link'
assert_not_contains config/navigation.php 'https://www\.facebook\.com/' 'generic Facebook navigation link'
assert_not_contains config/navigation.php 'https://www\.instagram\.com/' 'generic Instagram navigation link'

assert_contains config/footer.php 'TONBANKCARD' 'TONBANKCARD footer content'
assert_contains config/footer.php 'https://tonbankcard\.com' 'approved TONBANKCARD website link'
assert_contains config/footer.php 'https://t\.me/tonbankcard_chat' 'approved TONBANKCARD Telegram chat footer link'
assert_contains config/footer.php 'https://t\.me/tonbankcard_chat_ru' 'approved TONBANKCARD Telegram RU chat footer link'
assert_contains config/footer.php 'https://twitter\.com/tonbankcard' 'approved TONBANKCARD X/Twitter footer link'
assert_contains config/footer.php 'https://vk\.com/tonbankcard' 'approved TONBANKCARD VK footer link'
assert_not_contains config/footer.php '2021 Gecko Client' 'Gecko Client footer copyright'

assert_not_contains templates/routes/about.php 'Lorem ipsum|Nikolas Berry|Vincent Adams|Issac Nicholson|Paige Carson|Matteo Enriquez|Yousif Sharma|Serena Frost|Melisa Yu|Meet the team behind the development of this project' 'generic about/team placeholder copy'
assert_contains templates/routes/about.php 'TONBANKCARD Crypto Tracker' 'TONBANKCARD product context on the About route'
assert_contains templates/routes/about.php 'market pulse' 'market product positioning on the About route'

assert_contains templates/components/disclaimer-message.php 'market data' 'market-data disclaimer'
assert_contains templates/components/disclaimer-message.php 'AI summaries' 'AI summaries disclaimer'
assert_contains templates/components/disclaimer-message.php 'alerts' 'alert disclaimer'
assert_contains templates/components/disclaimer-message.php 'swap widgets' 'swap-widget disclaimer'

assert_contains templates/routes/terms.php 'market data' 'market-data legal disclosure'
assert_contains templates/routes/terms.php 'AI summaries' 'AI summaries legal disclosure'
assert_contains templates/routes/terms.php 'alerts' 'alert legal disclosure'
assert_contains templates/routes/terms.php 'swap widgets|ChangeNOW' 'exchange-widget legal disclosure'

assert_contains templates/routes/privacy-policy.php 'Telegram' 'Telegram privacy coverage'
assert_contains templates/routes/privacy-policy.php 'analytics' 'analytics privacy coverage'
assert_contains templates/routes/privacy-policy.php 'AI' 'AI privacy coverage'
assert_contains templates/routes/privacy-policy.php 'alerts' 'alert privacy coverage'
assert_contains templates/routes/privacy-policy.php 'wallet' 'wallet privacy coverage'
assert_contains templates/routes/privacy-policy.php 'Privacy_Policy_TONBANKCARD_en\.pdf' 'official English privacy PDF link'
assert_contains templates/routes/privacy-policy.php 'Privacy_Policy_TONBANKCARD_ru\.pdf' 'official Russian privacy PDF link'

assert_contains templates/routes/cookies-policy.php 'local storage' 'local storage policy coverage'
assert_contains templates/routes/cookies-policy.php 'English' 'English localization placeholder'
assert_contains templates/routes/cookies-policy.php 'Russian' 'Russian localization placeholder'

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Branding and content check passed.'
