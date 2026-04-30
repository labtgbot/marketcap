<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD RUNTIME CONFIGURATION
 * -------------------------------------------------------------------------
 * Environment helpers used before the Gecko Client bootstrap loads the
 * application configuration files.
 */

if ( ! function_exists( 'tonbankcard_load_env_file' ) ) {
    /**
     * Loads simple KEY=VALUE pairs from a local .env file without overriding
     * values already provided by the host environment.
     *
     * @param string $path
     * @return void
     */
    function tonbankcard_load_env_file( string $path ) {
        if ( ! is_readable( $path ) ) {
            return;
        }

        $lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        if ( ! is_array( $lines ) ) {
            return;
        }

        foreach ( $lines as $line ) {
            $line = trim( $line );

            if ( '' === $line || '#' === $line[0] ) {
                continue;
            }

            if ( 0 === strpos( $line, 'export ' ) ) {
                $line = trim( substr( $line, 7 ) );
            }

            $separator = strpos( $line, '=' );
            if ( FALSE === $separator ) {
                continue;
            }

            $key = trim( substr( $line, 0, $separator ) );
            $value = trim( substr( $line, $separator + 1 ) );

            if ( '' === $key || FALSE !== getenv( $key ) ) {
                continue;
            }

            $first = substr( $value, 0, 1 );
            $last  = substr( $value, -1 );
            if ( ( '"' === $first && '"' === $last ) || ( "'" === $first && "'" === $last ) ) {
                $value = substr( $value, 1, -1 );
            }

            putenv( $key . '=' . $value );
            $_ENV[ $key ] = $value;
            $_SERVER[ $key ] = $value;
        }
    }
}

if ( ! function_exists( 'tonbankcard_env' ) ) {
    /**
     * Reads an environment variable from getenv(), $_ENV, or $_SERVER.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function tonbankcard_env( string $key, $default = null ) {
        $value = getenv( $key );
        if ( FALSE !== $value ) {
            return $value;
        }

        if ( array_key_exists( $key, $_ENV ) ) {
            return $_ENV[ $key ];
        }

        if ( array_key_exists( $key, $_SERVER ) ) {
            return $_SERVER[ $key ];
        }

        return $default;
    }
}

if ( ! function_exists( 'tonbankcard_env_has' ) ) {
    /**
     * Checks whether an environment variable is present.
     *
     * @param string $key
     * @return bool
     */
    function tonbankcard_env_has( string $key ) {
        return FALSE !== getenv( $key ) || array_key_exists( $key, $_ENV ) || array_key_exists( $key, $_SERVER );
    }
}

if ( ! function_exists( 'tonbankcard_env_bool' ) ) {
    /**
     * Reads an environment variable as a boolean.
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    function tonbankcard_env_bool( string $key, bool $default = FALSE ) {
        $value = tonbankcard_env( $key, null );
        if ( null === $value || '' === $value ) {
            return $default;
        }

        if ( is_bool( $value ) ) {
            return $value;
        }

        $normalized = strtolower( trim( (string) $value ) );
        if ( in_array( $normalized, [ '1', 'true', 'yes', 'on' ], TRUE ) ) {
            return TRUE;
        }
        if ( in_array( $normalized, [ '0', 'false', 'no', 'off' ], TRUE ) ) {
            return FALSE;
        }

        return $default;
    }
}

if ( ! function_exists( 'tonbankcard_normalize_url' ) ) {
    /**
     * Normalizes configured absolute URLs while preserving empty values.
     *
     * @param string $url
     * @return string
     */
    function tonbankcard_normalize_url( string $url ) {
        $url = trim( $url );
        if ( '' === $url ) {
            return '';
        }

        return rtrim( $url, '/' ) . '/';
    }
}

if ( ! function_exists( 'tonbankcard_runtime_config' ) ) {
    /**
     * Builds runtime configuration from environment variables.
     *
     * @return array
     */
    function tonbankcard_runtime_config() {
        $profile = strtolower( trim( (string) tonbankcard_env( 'TONBANKCARD_PROFILE', 'local' ) ) );
        $aliases = [
            'dev'            => 'local',
            'development'    => 'local',
            'prod'           => 'production',
            'production-web' => 'production',
            'miniapp'        => 'telegram',
            'mini-app'       => 'telegram',
            'telegram-app'   => 'telegram',
        ];
        if ( isset( $aliases[ $profile ] ) ) {
            $profile = $aliases[ $profile ];
        }

        $local_url    = tonbankcard_normalize_url( (string) tonbankcard_env( 'TONBANKCARD_LOCAL_BASE_URL', 'http://localhost:8888/' ) );
        $staging_url  = tonbankcard_normalize_url( (string) tonbankcard_env( 'TONBANKCARD_STAGING_BASE_URL', '' ) );
        $public_url   = tonbankcard_normalize_url( (string) tonbankcard_env( 'TONBANKCARD_PUBLIC_BASE_URL', 'local' === $profile ? $local_url : '' ) );
        $telegram_url = tonbankcard_normalize_url( (string) tonbankcard_env( 'TONBANKCARD_TELEGRAM_BASE_URL', 'local' === $profile ? $local_url : '' ) );

        $active_url = tonbankcard_normalize_url( (string) tonbankcard_env( 'TONBANKCARD_BASE_URL', '' ) );
        if ( '' === $active_url ) {
            switch ( $profile ) {
                case 'local':
                    $active_url = $local_url;
                    break;
                case 'staging':
                    $active_url = $staging_url;
                    break;
                case 'telegram':
                    $active_url = $telegram_url;
                    break;
                case 'production':
                    $active_url = $public_url;
                    break;
            }
        }

        $feature_flags = [
            'ai'           => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_AI', FALSE ),
            'alerts'       => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_ALERTS', FALSE ),
            'changenow'    => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_CHANGENOW', FALSE ),
            'ton_connect'  => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_TON_CONNECT', FALSE ),
            'referrals'    => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_REFERRALS', FALSE ),
            'gamification' => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_GAMIFICATION', FALSE ),
            'premium'      => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_PREMIUM', FALSE ),
        ];

        $telegram_bot_token = (string) tonbankcard_env( 'TONBANKCARD_BOT_TOKEN', '' );
        $coingecko_api_key  = (string) tonbankcard_env( 'COINGECKO_API_KEY', '' );
        $groq_api_key       = (string) tonbankcard_env( 'GROQ_API_KEY', '' );
        $upstash_token      = (string) tonbankcard_env( 'UPSTASH_REDIS_REST_TOKEN', '' );
        $mysql_password     = (string) tonbankcard_env( 'MYSQL_PASSWORD', '' );

        return [
            'profile'       => $profile,
            'gecko_env'     => 'local' === $profile ? 'development' : 'production',
            'debug'         => tonbankcard_env_bool( 'TONBANKCARD_DEBUG', 'local' === $profile ),
            'assets'        => [
                'app_minified' => tonbankcard_env_bool( 'TONBANKCARD_APP_MINIFIED', 'local' !== $profile ),
                'preconnect'   => tonbankcard_env_bool( 'TONBANKCARD_PRECONNECT', TRUE ),
                'cdn'          => tonbankcard_env_bool( 'TONBANKCARD_CDN', FALSE ),
            ],
            'urls'          => [
                'active'   => $active_url,
                'local'    => $local_url,
                'staging'  => $staging_url,
                'public'   => $public_url,
                'telegram' => $telegram_url,
            ],
            'telegram'      => [
                'bot_username'          => (string) tonbankcard_env( 'TONBANKCARD_BOT_USERNAME', '' ),
                'bot_token'             => $telegram_bot_token,
                'bot_token_configured'  => '' !== trim( $telegram_bot_token ),
            ],
            'providers'     => [
                'coingecko' => [
                    'api_key'            => $coingecko_api_key,
                    'api_key_configured' => '' !== trim( $coingecko_api_key ),
                ],
                'groq'      => [
                    'api_key'            => $groq_api_key,
                    'api_key_configured' => '' !== trim( $groq_api_key ),
                ],
                'upstash'   => [
                    'rest_url'          => (string) tonbankcard_env( 'UPSTASH_REDIS_REST_URL', '' ),
                    'rest_token'        => $upstash_token,
                    'token_configured'  => '' !== trim( $upstash_token ),
                ],
                'mysql'     => [
                    'dsn'                 => (string) tonbankcard_env( 'MYSQL_DSN', '' ),
                    'user'                => (string) tonbankcard_env( 'MYSQL_USER', '' ),
                    'password'            => $mysql_password,
                    'password_configured' => '' !== trim( $mysql_password ),
                ],
                'changenow' => [
                    'link_id' => (string) tonbankcard_env( 'CHANGENOW_LINK_ID', '' ),
                ],
            ],
            'feature_flags' => $feature_flags,
        ];
    }
}
