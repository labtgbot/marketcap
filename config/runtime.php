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

if ( ! function_exists( 'tonbankcard_runtime_app_root' ) ) {
    /**
     * Returns the application root directory.
     *
     * @return string
     */
    function tonbankcard_runtime_app_root() {
        return defined( 'GECKO_CLIENT_DIR' ) ? GECKO_CLIENT_DIR : dirname( __DIR__ );
    }
}

if ( ! function_exists( 'tonbankcard_runtime_state_dir' ) ) {
    /**
     * Returns the private directory used by default for sensitive local JSON state.
     *
     * @return string
     */
    function tonbankcard_runtime_state_dir() {
        $configured = trim( (string) tonbankcard_env( 'TONBANKCARD_STATE_DIR', '' ) );
        if ( '' !== $configured ) {
            $trimmed = rtrim( $configured, DIRECTORY_SEPARATOR );
            return '' === $trimmed ? $configured : $trimmed;
        }

        return dirname( tonbankcard_runtime_app_root() ) . DIRECTORY_SEPARATOR . '.tonbankcard-marketcap-state';
    }
}

if ( ! function_exists( 'tonbankcard_runtime_state_store_path' ) ) {
    /**
     * Builds a default sensitive JSON store path inside the private state dir.
     *
     * @param string $filename
     * @return string
     */
    function tonbankcard_runtime_state_store_path( string $filename ) {
        return tonbankcard_runtime_state_dir() . DIRECTORY_SEPARATOR . ltrim( $filename, DIRECTORY_SEPARATOR );
    }
}

if ( ! function_exists( 'tonbankcard_runtime_current_uid' ) ) {
    /**
     * Returns the effective process UID when the platform exposes it.
     *
     * @return int|null
     */
    function tonbankcard_runtime_current_uid() {
        return function_exists( 'posix_geteuid' ) ? (int) posix_geteuid() : null;
    }
}

if ( ! function_exists( 'tonbankcard_runtime_sensitive_store_error' ) ) {
    /**
     * Builds a typed sensitive-store validation error.
     *
     * @param string $code
     * @param string $message
     * @param array $details
     * @return array
     */
    function tonbankcard_runtime_sensitive_store_error( string $code, string $message, array $details = [] ) {
        return [
            'ok'      => FALSE,
            'code'    => $code,
            'message' => $message,
            'details' => $details,
        ];
    }
}

if ( ! function_exists( 'tonbankcard_runtime_path_owned_by_current_user' ) ) {
    /**
     * Checks whether a path is owned by the current process user when possible.
     *
     * @param string $path
     * @return bool
     */
    function tonbankcard_runtime_path_owned_by_current_user( string $path ) {
        $uid = tonbankcard_runtime_current_uid();
        if ( null === $uid ) {
            return TRUE;
        }

        $owner = @fileowner( $path );
        return FALSE !== $owner && (int) $owner === $uid;
    }
}

if ( ! function_exists( 'tonbankcard_runtime_private_directory_status' ) ) {
    /**
     * Validates a sensitive-store directory without silently weakening fail-closed behavior.
     *
     * @param string $dir
     * @param bool $create
     * @return array
     */
    function tonbankcard_runtime_private_directory_status( string $dir, bool $create ) {
        if ( '' === trim( $dir ) || FALSE !== strpos( $dir, "\0" ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_directory_invalid',
                'Sensitive store directory path is invalid.',
                [ 'path' => $dir ]
            );
        }

        $created = FALSE;
        if ( ! file_exists( $dir ) ) {
            if ( ! $create ) {
                return tonbankcard_runtime_sensitive_store_error(
                    'sensitive_store_directory_missing',
                    'Sensitive store directory does not exist.',
                    [ 'path' => $dir ]
                );
            }
            if ( ! @mkdir( $dir, 0700, TRUE ) && ! is_dir( $dir ) ) {
                return tonbankcard_runtime_sensitive_store_error(
                    'sensitive_store_directory_create_failed',
                    'Sensitive store directory could not be created.',
                    [ 'path' => $dir ]
                );
            }
            $created = TRUE;
            @chmod( $dir, 0700 );
        }

        if ( is_link( $dir ) || ! is_dir( $dir ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_directory_invalid',
                'Sensitive store directory must be a real directory.',
                [ 'path' => $dir ]
            );
        }

        if ( ! tonbankcard_runtime_path_owned_by_current_user( $dir ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_directory_owner_mismatch',
                'Sensitive store directory must be owned by the PHP process user.',
                [ 'path' => $dir ]
            );
        }

        $perms = @fileperms( $dir );
        if ( FALSE === $perms || 0 !== ( $perms & 0077 ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_directory_not_private',
                'Sensitive store directory must not be accessible to group or other users.',
                [
                    'path' => $dir,
                    'mode' => FALSE === $perms ? null : sprintf( '%04o', $perms & 0777 ),
                ]
            );
        }

        if ( ! is_writable( $dir ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_directory_not_writable',
                'Sensitive store directory must be writable by the PHP process.',
                [ 'path' => $dir ]
            );
        }

        return [
            'ok'      => TRUE,
            'path'    => $dir,
            'created' => $created,
        ];
    }
}

if ( ! function_exists( 'tonbankcard_runtime_private_file_status' ) ) {
    /**
     * Validates an existing sensitive-store file.
     *
     * @param string $path
     * @param string $mode
     * @return array
     */
    function tonbankcard_runtime_private_file_status( string $path, string $mode ) {
        if ( is_link( $path ) || ! is_file( $path ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_file_invalid',
                'Sensitive store path must be a real file.',
                [ 'path' => $path ]
            );
        }

        if ( ! tonbankcard_runtime_path_owned_by_current_user( $path ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_file_owner_mismatch',
                'Sensitive store file must be owned by the PHP process user.',
                [ 'path' => $path ]
            );
        }

        $perms = @fileperms( $path );
        if ( FALSE === $perms || 0 !== ( $perms & 0077 ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_file_not_private',
                'Sensitive store file must not be accessible to group or other users.',
                [
                    'path' => $path,
                    'mode' => FALSE === $perms ? null : sprintf( '%04o', $perms & 0777 ),
                ]
            );
        }

        if ( 'read' === $mode && ! is_readable( $path ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_file_not_readable',
                'Sensitive store file must be readable by the PHP process.',
                [ 'path' => $path ]
            );
        }

        if ( 'write' === $mode && ! is_writable( $path ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_file_not_writable',
                'Sensitive store file must be writable by the PHP process.',
                [ 'path' => $path ]
            );
        }

        return [
            'ok'   => TRUE,
            'path' => $path,
        ];
    }
}

if ( ! function_exists( 'tonbankcard_runtime_sensitive_store_status' ) ) {
    /**
     * Validates a sensitive local JSON store path for fail-closed reads and writes.
     *
     * @param string $path
     * @param array $options
     * @return array
     */
    function tonbankcard_runtime_sensitive_store_status( string $path, array $options = [] ) {
        $path = trim( $path );
        $mode = isset( $options['mode'] ) && 'write' === $options['mode'] ? 'write' : 'read';
        $create_dir = ! empty( $options['create_dir'] );
        $require_file = ! empty( $options['require_file'] );

        if ( '' === $path || FALSE !== strpos( $path, "\0" ) ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_path_invalid',
                'Sensitive store path is invalid.',
                [ 'path' => $path ]
            );
        }

        $dir = dirname( $path );
        $dir_status = tonbankcard_runtime_private_directory_status( $dir, $create_dir );
        if ( empty( $dir_status['ok'] ) ) {
            return $dir_status;
        }

        if ( file_exists( $path ) ) {
            $file_status = tonbankcard_runtime_private_file_status( $path, $mode );
            if ( empty( $file_status['ok'] ) ) {
                return $file_status;
            }
        } elseif ( $require_file ) {
            return tonbankcard_runtime_sensitive_store_error(
                'sensitive_store_file_missing',
                'Sensitive store file does not exist.',
                [ 'path' => $path ]
            );
        }

        return [
            'ok'     => TRUE,
            'path'   => $path,
            'dir'    => $dir,
            'exists' => is_file( $path ),
        ];
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

        return tonbankcard_runtime_state_store_path( 'tonbankcard-marketcap-admin.json' );
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
        if ( '' === trim( $path ) || ! is_file( $path ) ) {
            return [];
        }

        $status = tonbankcard_runtime_sensitive_store_status(
            $path,
            [
                'mode'         => 'read',
                'require_file' => TRUE,
            ]
        );
        if ( empty( $status['ok'] ) || ! is_readable( $path ) ) {
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

        $active_url_parts = parse_url( $active_url );
        $active_url_is_https = is_array( $active_url_parts )
            && ! empty( $active_url_parts['scheme'] )
            && 'https' === strtolower( (string) $active_url_parts['scheme'] );
        $force_https = tonbankcard_env_bool( 'TONBANKCARD_FORCE_HTTPS', 'local' !== $profile );
        $hsts_enabled = tonbankcard_env_bool( 'TONBANKCARD_HSTS_ENABLED', $force_https && 'local' !== $profile );

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
        $telegram_bot_webhook_secret = (string) tonbankcard_env( 'TONBANKCARD_BOT_WEBHOOK_SECRET', '' );
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
        $tonapi_api_key     = (string) tonbankcard_env( 'TONAPI_API_KEY', '' );
        $upstash_token      = (string) tonbankcard_env( 'UPSTASH_REDIS_REST_TOKEN', '' );
        $mysql_password     = (string) tonbankcard_env( 'MYSQL_PASSWORD', '' );
        $changenow_link_id     = tonbankcard_runtime_admin_scalar( $admin_store, [ 'providers', 'changenow', 'link_id' ], (string) tonbankcard_env( 'CHANGENOW_LINK_ID', '' ) );
        $changenow_listing_url = tonbankcard_runtime_admin_scalar( $admin_store, [ 'providers', 'changenow', 'listing_url' ], (string) tonbankcard_env( 'CHANGENOW_LISTING_URL', '' ) );
        $yandex_metrica_counter_id = preg_replace( '/\D+/', '', (string) tonbankcard_env( 'YANDEX_METRICA_COUNTER_ID', '' ) );
        if ( null === $yandex_metrica_counter_id ) {
            $yandex_metrica_counter_id = '';
        }
        $yandex_metrica_enabled = tonbankcard_env_bool( 'YANDEX_METRICA_ENABLED', FALSE ) && '' !== $yandex_metrica_counter_id;
        if ( isset( $admin_store['analytics']['yandex_metrica'] ) && is_array( $admin_store['analytics']['yandex_metrica'] ) ) {
            $admin_metrica = $admin_store['analytics']['yandex_metrica'];
            if ( array_key_exists( 'counter_id', $admin_metrica ) ) {
                $yandex_metrica_counter_id = preg_replace( '/\D+/', '', (string) $admin_metrica['counter_id'] );
                if ( null === $yandex_metrica_counter_id ) {
                    $yandex_metrica_counter_id = '';
                }
            }
            if ( array_key_exists( 'enabled', $admin_metrica ) ) {
                $yandex_metrica_enabled = (bool) $admin_metrica['enabled'] && '' !== $yandex_metrica_counter_id;
            }
        }
        $observability_log_level = strtolower( trim( (string) tonbankcard_env( 'TONBANKCARD_OBSERVABILITY_LOG_LEVEL', 'warning' ) ) );
        if ( ! in_array( $observability_log_level, [ 'debug', 'info', 'warning', 'warn', 'error', 'critical', 'off' ], TRUE ) ) {
            $observability_log_level = 'warning';
        }
        if ( 'warn' === $observability_log_level ) {
            $observability_log_level = 'warning';
        }

        $uptime_base_url = tonbankcard_normalize_url( (string) tonbankcard_env( 'TONBANKCARD_UPTIME_MONITOR_BASE_URL', $active_url ) );
        $uptime_targets  = [];
        foreach ( explode( ',', (string) tonbankcard_env( 'TONBANKCARD_UPTIME_MONITOR_TARGETS', '/api/health,/api/ready' ) ) as $uptime_target ) {
            $uptime_target = trim( $uptime_target );
            if ( '' === $uptime_target ) {
                continue;
            }
            if ( '/' !== $uptime_target[0] ) {
                $uptime_target = '/' . $uptime_target;
            }
            if ( ! in_array( $uptime_target, $uptime_targets, TRUE ) ) {
                $uptime_targets[] = $uptime_target;
            }
        }
        if ( empty( $uptime_targets ) ) {
            $uptime_targets = [ '/api/health', '/api/ready' ];
        }
        $uptime_bot_token = (string) tonbankcard_env( 'TONBANKCARD_UPTIME_MONITOR_BOT_TOKEN', $telegram_bot_token );

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
            'security'      => [
                'force_https'    => $force_https,
                'hsts_enabled'   => $hsts_enabled,
                'hsts_header'    => 'max-age=63072000; includeSubDomains; preload',
                'secure_cookies' => tonbankcard_env_bool( 'TONBANKCARD_SECURE_COOKIES', $force_https || $active_url_is_https ),
            ],
            'telegram'      => [
                'bot_username'              => (string) tonbankcard_env( 'TONBANKCARD_BOT_USERNAME', '' ),
                'bot_token'                 => $telegram_bot_token,
                'bot_token_configured'      => '' !== trim( $telegram_bot_token ),
                'webhook_secret'            => $telegram_bot_webhook_secret,
                'webhook_secret_configured' => '' !== trim( $telegram_bot_webhook_secret ),
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
                'tonapi'    => [
                    'api_key'            => $tonapi_api_key,
                    'api_key_configured' => '' !== trim( $tonapi_api_key ),
                ],
                'mysql'     => [
                    'dsn'                 => (string) tonbankcard_env( 'MYSQL_DSN', '' ),
                    'user'                => (string) tonbankcard_env( 'MYSQL_USER', '' ),
                    'password'            => $mysql_password,
                    'password_configured' => '' !== trim( $mysql_password ),
                ],
                'changenow' => [
                    'link_id'     => $changenow_link_id,
                    'listing_url' => $changenow_listing_url,
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
                'error_monitoring'       => [
                    'enabled'      => tonbankcard_env_bool( 'TONBANKCARD_ERROR_MONITORING_ENABLED', FALSE ),
                    'dsn'          => (string) tonbankcard_env( 'TONBANKCARD_ERROR_MONITORING_DSN', '' ),
                    'min_level'    => strtolower( trim( (string) tonbankcard_env( 'TONBANKCARD_ERROR_MONITORING_MIN_LEVEL', 'error' ) ) ),
                    'environment'  => (string) tonbankcard_env( 'TONBANKCARD_ERROR_MONITORING_ENVIRONMENT', $profile ),
                    'timeout_ms'   => tonbankcard_env_int( 'TONBANKCARD_ERROR_MONITORING_TIMEOUT_MS', 2000, 100, 15000 ),
                ],
                'uptime'                 => [
                    'enabled'     => tonbankcard_env_bool( 'TONBANKCARD_UPTIME_MONITOR_ENABLED', FALSE ),
                    'base_url'    => $uptime_base_url,
                    'targets'     => $uptime_targets,
                    'bot_token'   => $uptime_bot_token,
                    'chat_id'     => trim( (string) tonbankcard_env( 'TONBANKCARD_UPTIME_MONITOR_TELEGRAM_CHAT_ID', '' ) ),
                    'timeout_ms'  => tonbankcard_env_int( 'TONBANKCARD_UPTIME_MONITOR_TIMEOUT_MS', 5000, 500, 30000 ),
                    'environment' => (string) tonbankcard_env( 'TONBANKCARD_UPTIME_MONITOR_ENVIRONMENT', $profile ),
                ],
            ],
            'analytics'     => [
                'yandex_metrica' => [
                    'counter_id' => $yandex_metrica_counter_id,
                    'enabled'    => $yandex_metrica_enabled,
                ],
            ],
            'feature_flags' => $feature_flags,
        ];
    }
}
