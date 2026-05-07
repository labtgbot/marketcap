<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 CHANGENOW PROXY
 * -------------------------------------------------------------------------
 * Server-side proxy for the ChangeNOW public currencies API.
 * Fetches available tickers and caches them to avoid repeated external calls.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

const CHANGENOW_CURRENCIES_URL    = 'https://api.changenow.io/v1/currencies?active=true&fixedRate=false';
const CHANGENOW_CACHE_TTL_SECONDS = 3600;

/**
 * Returns TRUE when the path is a ChangeNOW proxy request.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_changenow_is_request( string $path ): bool {
    return '/api/changenow/currencies' === $path;
}

/**
 * Handles a ChangeNOW currencies request.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_changenow_handle( array $request, array $runtime, array $config, string $request_id, array $headers ): array {
    if ( 'GET' !== $request['method'] ) {
        return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
    }

    $tickers = tonbankcard_api_changenow_get_tickers( $runtime, $config );
    if ( isset( $tickers['error'] ) ) {
        return tonbankcard_api_error_response(
            502,
            'provider_unavailable',
            'Could not fetch the ChangeNOW currencies list.',
            [ 'reason' => $tickers['error'] ],
            $request_id,
            $headers
        );
    }

    return tonbankcard_api_success_response(
        [ 'tickers' => $tickers['tickers'] ],
        $request_id,
        $headers,
        200,
        [ 'source' => $tickers['source'] ]
    );
}

/**
 * Returns the list of ChangeNOW tickers, using a server-side file cache.
 *
 * @param array $runtime
 * @param array $config
 * @return array  On success: ['tickers' => string[], 'source' => string]
 *                On failure: ['error' => string]
 */
function tonbankcard_api_changenow_get_tickers( array $runtime, array $config ): array {
    $cache_file = sys_get_temp_dir() . '/tbc_changenow_tickers.json';
    $now        = time();

    if ( file_exists( $cache_file ) ) {
        $raw = @file_get_contents( $cache_file );
        if ( FALSE !== $raw ) {
            $cached = json_decode( $raw, TRUE );
            if (
                is_array( $cached )
                && isset( $cached['tickers'], $cached['expires_at'] )
                && is_array( $cached['tickers'] )
                && $cached['expires_at'] > $now
            ) {
                return [ 'tickers' => $cached['tickers'], 'source' => 'cache' ];
            }
        }
    }

    $result = tonbankcard_api_changenow_fetch_tickers();
    if ( isset( $result['error'] ) ) {
        return $result;
    }

    $payload = json_encode(
        [
            'tickers'    => $result['tickers'],
            'expires_at' => $now + CHANGENOW_CACHE_TTL_SECONDS,
        ],
        JSON_UNESCAPED_SLASHES
    );
    if ( FALSE !== $payload ) {
        @file_put_contents( $cache_file, $payload, LOCK_EX );
    }

    return [ 'tickers' => $result['tickers'], 'source' => 'live' ];
}

/**
 * Fetches the ChangeNOW currencies list via HTTP and extracts tickers.
 *
 * @return array  On success: ['tickers' => string[]]
 *                On failure: ['error' => string]
 */
function tonbankcard_api_changenow_fetch_tickers(): array {
    if ( function_exists( 'curl_init' ) ) {
        $handle = curl_init( CHANGENOW_CURRENCIES_URL );
        if ( FALSE === $handle ) {
            return [ 'error' => 'curl_init_failed' ];
        }

        curl_setopt_array( $handle, [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [ 'Accept: application/json' ],
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXREDIRS      => 3,
        ] );

        $body   = curl_exec( $handle );
        $errno  = curl_errno( $handle );
        $status = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE );
        curl_close( $handle );

        if ( 0 !== $errno || FALSE === $body ) {
            return [ 'error' => 'curl_fetch_failed' ];
        }
        if ( $status < 200 || $status >= 300 ) {
            return [ 'error' => 'upstream_http_' . $status ];
        }
    } else {
        $context = stream_context_create( [
            'http' => [
                'method'          => 'GET',
                'header'          => "Accept: application/json\r\n",
                'timeout'         => 10,
                'ignore_errors'   => TRUE,
            ],
        ] );
        $body = @file_get_contents( CHANGENOW_CURRENCIES_URL, FALSE, $context );
        if ( FALSE === $body ) {
            return [ 'error' => 'stream_fetch_failed' ];
        }
    }

    $data = json_decode( $body, TRUE );
    if ( ! is_array( $data ) ) {
        return [ 'error' => 'invalid_json_response' ];
    }

    $tickers = [];
    foreach ( $data as $entry ) {
        if ( isset( $entry['ticker'] ) && is_string( $entry['ticker'] ) && '' !== $entry['ticker'] ) {
            $tickers[] = strtolower( $entry['ticker'] );
        }
    }

    return [ 'tickers' => $tickers ];
}
