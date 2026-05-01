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

if ( ! function_exists( 'tonbankcard_env_int' ) ) {
    /**
     * Reads an environment variable as a bounded integer.
     *
     * @param string $key
     * @param int $default
     * @param int $min
     * @param int $max
     * @return int
     */
    function tonbankcard_env_int( string $key, int $default, int $min, int $max ) {
        $value = tonbankcard_env( $key, null );
        if ( null === $value || '' === trim( (string) $value ) || ! is_numeric( $value ) ) {
            return $default;
        }

        $int_value = (int) $value;
        if ( $int_value < $min ) {
            return $min;
        }
        if ( $int_value > $max ) {
            return $max;
        }

        return $int_value;
    }
}

if ( ! function_exists( 'tonbankcard_env_list' ) ) {
    /**
     * Reads an environment variable as a comma-separated safe list.
     *
     * @param string $key
     * @param array $default
     * @param array $allowed
     * @return array
     */
    function tonbankcard_env_list( string $key, array $default, array $allowed ) {
        $value = tonbankcard_env( $key, null );
        if ( null === $value || '' === trim( (string) $value ) ) {
            return $default;
        }

        $items = [];
        foreach ( explode( ',', (string) $value ) as $item ) {
            $item = strtolower( trim( $item ) );
            if ( '' !== $item && in_array( $item, $allowed, TRUE ) && ! in_array( $item, $items, TRUE ) ) {
                $items[] = $item;
            }
        }

        return empty( $items ) ? $default : $items;
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

if ( ! function_exists( 'tonbankcard_runtime_admin_store_path' ) ) {
    /**
     * Returns the JSON admin configuration store path.
     *
     * @return string
     */
    function tonbankcard_runtime_admin_store_path() {
        $path = trim( (string) tonbankcard_env( 'TONBANKCARD_ADMIN_STORE', '' ) );
        if ( '' !== $path ) {
            return $path;
        }

        return sys_get_temp_dir() . '/tonbankcard-marketcap-admin.json';
    }
}

if ( ! function_exists( 'tonbankcard_runtime_admin_store' ) ) {
    /**
     * Reads the admin configuration store without exposing raw secrets.
     *
     * @param string|null $path
     * @return array
     */
    function tonbankcard_runtime_admin_store( $path = null ) {
        $path = null === $path ? tonbankcard_runtime_admin_store_path() : (string) $path;
        if ( '' === trim( $path ) || ! is_file( $path ) || ! is_readable( $path ) ) {
            return [];
        }

        $raw = file_get_contents( $path );
        if ( FALSE === $raw || '' === trim( $raw ) ) {
            return [];
        }

        $decoded = json_decode( $raw, TRUE );
        return is_array( $decoded ) ? $decoded : [];
    }
}

if ( ! function_exists( 'tonbankcard_runtime_admin_bool_overrides' ) ) {
    /**
     * Merges boolean feature overrides saved from the admin panel.
     *
     * @param array $feature_flags
     * @param array $store
     * @return array
     */
    function tonbankcard_runtime_admin_bool_overrides( array $feature_flags, array $store ) {
        $overrides = isset( $store['feature_flags'] ) && is_array( $store['feature_flags'] ) ? $store['feature_flags'] : [];
        foreach ( [ 'ai', 'alerts', 'widget', 'changenow', 'ton_connect', 'referrals', 'gamification', 'premium' ] as $flag ) {
            if ( array_key_exists( $flag, $overrides ) ) {
                $feature_flags[ $flag ] = (bool) $overrides[ $flag ];
            }
        }

        if ( array_key_exists( 'widget', $overrides ) ) {
            $feature_flags['changenow'] = (bool) $overrides['widget'];
        } elseif ( array_key_exists( 'changenow', $overrides ) ) {
            $feature_flags['widget'] = (bool) $overrides['changenow'];
        } elseif ( isset( $feature_flags['changenow'] ) && ! isset( $feature_flags['widget'] ) ) {
            $feature_flags['widget'] = (bool) $feature_flags['changenow'];
        }

        return $feature_flags;
    }
}

if ( ! function_exists( 'tonbankcard_runtime_admin_scalar' ) ) {
    /**
     * Reads a bounded scalar override from the admin store.
     *
     * @param array $store
     * @param array $path
     * @param string $default
     * @param array $allowed
     * @return string
     */
    function tonbankcard_runtime_admin_scalar( array $store, array $path, string $default, array $allowed = [] ) {
        $value = $store;
        foreach ( $path as $part ) {
            if ( ! is_array( $value ) || ! array_key_exists( $part, $value ) ) {
                return $default;
            }
            $value = $value[ $part ];
        }

        if ( ! is_scalar( $value ) ) {
            return $default;
        }

        $value = trim( (string) $value );
        if ( '' === $value ) {
            return $default;
        }

        if ( ! empty( $allowed ) && ! in_array( $value, $allowed, TRUE ) ) {
            return $default;
        }

        return $value;
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

        $changenow_feature = tonbankcard_env_bool( 'TONBANKCARD_FEATURE_CHANGENOW', FALSE );
        $feature_flags = [
            'ai'           => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_AI', FALSE ),
            'alerts'       => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_ALERTS', FALSE ),
            'changenow'    => $changenow_feature,
            'widget'       => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_WIDGET', $changenow_feature ),
            'ton_connect'  => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_TON_CONNECT', FALSE ),
            'referrals'    => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_REFERRALS', FALSE ),
            'gamification' => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_GAMIFICATION', FALSE ),
            'premium'      => tonbankcard_env_bool( 'TONBANKCARD_FEATURE_PREMIUM', FALSE ),
        ];
        $admin_store_path = tonbankcard_runtime_admin_store_path();
        $admin_store = tonbankcard_runtime_admin_store( $admin_store_path );
        $feature_flags = tonbankcard_runtime_admin_bool_overrides( $feature_flags, $admin_store );

        $telegram_bot_token = (string) tonbankcard_env( 'TONBANKCARD_BOT_TOKEN', '' );
        $coingecko_api_key  = (string) tonbankcard_env( 'COINGECKO_API_KEY', '' );
        $coingecko_api_plan = strtolower( trim( (string) tonbankcard_env( 'COINGECKO_API_PLAN', 'demo' ) ) );
        if ( ! in_array( $coingecko_api_plan, [ 'demo', 'pro' ], TRUE ) ) {
            $coingecko_api_plan = 'demo';
        }
        $coingecko_api_plan = tonbankcard_runtime_admin_scalar( $admin_store, [ 'providers', 'coingecko', 'api_plan' ], $coingecko_api_plan, [ 'demo', 'pro' ] );
        $ai_provider = strtolower( trim( (string) tonbankcard_env( 'TONBANKCARD_AI_PROVIDER', 'groq' ) ) );
        if ( ! in_array( $ai_provider, [ 'groq' ], TRUE ) ) {
            $ai_provider = 'groq';
        }
        $ai_prompt_version = trim( (string) tonbankcard_env( 'TONBANKCARD_AI_PROMPT_VERSION', 'v1' ) );
        if ( '' === $ai_prompt_version ) {
            $ai_prompt_version = 'v1';
        }
        $ai_fallback_behavior = strtolower( trim( (string) tonbankcard_env( 'TONBANKCARD_AI_FALLBACK_BEHAVIOR', 'unavailable' ) ) );
        if ( ! in_array( $ai_fallback_behavior, [ 'unavailable' ], TRUE ) ) {
            $ai_fallback_behavior = 'unavailable';
        }
        $ai_enabled_features = tonbankcard_env_list(
            'TONBANKCARD_AI_ENABLED_FEATURES',
            [ 'summary', 'sentiment', 'insight' ],
            [ 'summary', 'sentiment', 'insight' ]
        );

        $groq_api_key       = (string) tonbankcard_env( 'GROQ_API_KEY', '' );
        $groq_model_id      = trim( (string) tonbankcard_env( 'GROQ_MODEL_ID', 'llama-3.3-70b-versatile' ) );
        if ( '' === $groq_model_id ) {
            $groq_model_id = 'llama-3.3-70b-versatile';
        }
        $groq_model_id = tonbankcard_runtime_admin_scalar( $admin_store, [ 'providers', 'groq', 'model_id' ], $groq_model_id );
        $groq_base_url      = tonbankcard_normalize_url( (string) tonbankcard_env( 'GROQ_BASE_URL', 'https://api.groq.com/openai/v1/' ) );
        $upstash_token      = (string) tonbankcard_env( 'UPSTASH_REDIS_REST_TOKEN', '' );
        $mysql_password     = (string) tonbankcard_env( 'MYSQL_PASSWORD', '' );
        $changenow_link_id  = tonbankcard_runtime_admin_scalar( $admin_store, [ 'providers', 'changenow', 'link_id' ], (string) tonbankcard_env( 'CHANGENOW_LINK_ID', '' ) );
        $observability_log_level = strtolower( trim( (string) tonbankcard_env( 'TONBANKCARD_OBSERVABILITY_LOG_LEVEL', 'warning' ) ) );
        if ( ! in_array( $observability_log_level, [ 'debug', 'info', 'warning', 'warn', 'error', 'critical', 'off' ], TRUE ) ) {
            $observability_log_level = 'warning';
        }
        if ( 'warn' === $observability_log_level ) {
            $observability_log_level = 'warning';
        }

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
            'ai'            => [
                'provider'          => $ai_provider,
                'prompt_version'    => $ai_prompt_version,
                'enabled_features'  => $ai_enabled_features,
                'fallback_behavior' => $ai_fallback_behavior,
            ],
            'providers'     => [
                'coingecko' => [
                    'api_key'            => $coingecko_api_key,
                    'api_key_configured' => '' !== trim( $coingecko_api_key ),
                    'api_plan'           => $coingecko_api_plan,
                ],
                'groq'      => [
                    'api_key'            => $groq_api_key,
                    'api_key_configured' => '' !== trim( $groq_api_key ),
                    'model_id'           => $groq_model_id,
                    'base_url'           => $groq_base_url,
                    'timeout_seconds'    => tonbankcard_env_int( 'GROQ_TIMEOUT_SECONDS', 10, 1, 120 ),
                    'rate_limit'         => [
                        'window_seconds' => tonbankcard_env_int( 'GROQ_RATE_LIMIT_WINDOW_SECONDS', 60, 1, 3600 ),
                        'max_requests'   => tonbankcard_env_int( 'GROQ_RATE_LIMIT_MAX_REQUESTS', 20, 1, 10000 ),
                    ],
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
                    'link_id' => $changenow_link_id,
                ],
            ],
            'admin'         => [
                'store_path'                => $admin_store_path,
                'store_configured'          => tonbankcard_env_has( 'TONBANKCARD_ADMIN_STORE' ),
                'store_loaded'              => ! empty( $admin_store ),
                'token_configured'          => '' !== trim( (string) tonbankcard_env( 'TONBANKCARD_ADMIN_TOKEN', '' ) ),
                'support_token_configured'  => '' !== trim( (string) tonbankcard_env( 'TONBANKCARD_ADMIN_SUPPORT_TOKEN', '' ) ),
            ],
            'observability' => [
                'log_level'              => $observability_log_level,
                'verbose_tracing'        => tonbankcard_env_bool( 'TONBANKCARD_VERBOSE_TRACING', FALSE ),
                'client_error_reporting' => tonbankcard_env_bool( 'TONBANKCARD_CLIENT_ERROR_REPORTING', TRUE ),
                'sink'                   => 'error_log',
            ],
            'feature_flags' => $feature_flags,
        ];
    }
}
