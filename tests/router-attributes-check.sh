#!/usr/bin/env sh
set -eu

failures=0

fail() {
    printf '%s\n' "FAIL: $1" >&2
    failures=$((failures + 1))
}

php_check() {
    description=$1
    shift

    if ! "$@"; then
        fail "$description"
    fi
}

php_check 'router helper attributes should render Vue route params without PHP 8 implode failures' \
    env -i PATH="$PATH" \
        TONBANKCARD_PROFILE=local \
        php <<'PHP'
<?php
require 'constants.php';
require __DIR__ . '/functions.php';

function assert_same_value( $expected, $actual, $message ) {
    if ( $expected !== $actual ) {
        fwrite( STDERR, $message . ': expected [' . $expected . '], got [' . $actual . ']' . PHP_EOL );
        exit( 1 );
    }
}

assert_same_value(
    ':to="{name:\'currency\',params:{\'id\':\'bitcoin\'}}"',
    to_attr( 'currency', [ 'id' => 'bitcoin' ], false ),
    'to_attr should include params inside the Vue route object'
);

assert_same_value(
    ':to="{name:\'currency\',params:{\'id\':\'bitcoin\',\'tab\':\'markets\'}}" exact',
    link_attrs(
        [
            'route' => [
                'name'   => 'currency',
                'params' => [
                    'id'  => 'bitcoin',
                    'tab' => 'markets',
                ],
            ],
        ],
        [],
        false
    ),
    'link_attrs should include array-route params inside the Vue route object'
);
PHP

if [ "$failures" -gt 0 ]; then
    exit 1
fi

printf '%s\n' 'Router attribute check passed.'
