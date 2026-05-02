<?php
/**
 * TONBANKCARD automatic hosting installer helpers.
 */

if ( ! function_exists( 'tonbankcard_installer_root_dir' ) ) {
    /**
     * Returns the repository root for installer operations.
     *
     * @return string
     */
    function tonbankcard_installer_root_dir() {
        return dirname( __DIR__, 2 );
    }
}

if ( ! function_exists( 'tonbankcard_installer_env_example_path' ) ) {
    /**
     * @param string|null $root
     * @return string
     */
    function tonbankcard_installer_env_example_path( $root = null ) {
        $root = null === $root ? tonbankcard_installer_root_dir() : rtrim( (string) $root, '/' );
        return $root . '/.env.example';
    }
}

if ( ! function_exists( 'tonbankcard_installer_env_path' ) ) {
    /**
     * @param string|null $root
     * @return string
     */
    function tonbankcard_installer_env_path( $root = null ) {
        $root = null === $root ? tonbankcard_installer_root_dir() : rtrim( (string) $root, '/' );
        return $root . '/.env';
    }
}

if ( ! function_exists( 'tonbankcard_installer_unquote_env_value' ) ) {
    /**
     * @param string $value
     * @return string
     */
    function tonbankcard_installer_unquote_env_value( string $value ) {
        $value = trim( $value );
        if ( '' === $value ) {
            return '';
        }

        $first = substr( $value, 0, 1 );
        $last  = substr( $value, -1 );
        if ( ( '"' === $first && '"' === $last ) || ( "'" === $first && "'" === $last ) ) {
            $value = substr( $value, 1, -1 );
        }

        return str_replace( [ '\\"', '\\\\' ], [ '"', '\\' ], $value );
    }
}

if ( ! function_exists( 'tonbankcard_installer_parse_env_file' ) ) {
    /**
     * Parses simple KEY=VALUE dotenv files.
     *
     * @param string $path
     * @return array
     */
    function tonbankcard_installer_parse_env_file( string $path ) {
        if ( ! is_readable( $path ) ) {
            return [];
        }

        $lines = file( $path, FILE_IGNORE_NEW_LINES );
        if ( ! is_array( $lines ) ) {
            return [];
        }

        $values = [];
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
            if ( '' === $key ) {
                continue;
            }

            $values[ $key ] = tonbankcard_installer_unquote_env_value( substr( $line, $separator + 1 ) );
        }

        return $values;
    }
}

if ( ! function_exists( 'tonbankcard_installer_field_groups' ) ) {
    /**
     * Returns grouped form fields for the installation wizard.
     *
     * @return array
     */
    function tonbankcard_installer_field_groups() {
        return [
            'runtime'      => [
                'title'       => 'Step 2. Runtime profile and public URLs',
                'description' => 'Choose the deployment mode and the exact HTTPS URLs that users and Telegram will open.',
                'fields'      => [
                    'TONBANKCARD_PROFILE'           => [
                        'label'   => 'Runtime profile',
                        'type'    => 'select',
                        'options' => [ 'production', 'telegram', 'staging', 'local' ],
                        'help'    => 'Use production for the public website and telegram for a Mini App-first deployment.',
                    ],
                    'TONBANKCARD_PUBLIC_BASE_URL'   => [
                        'label'       => 'Public website URL',
                        'type'        => 'url',
                        'placeholder' => 'https://marketcap.example.com/',
                        'help'        => 'Canonical website URL used for public pages and shared links.',
                    ],
                    'TONBANKCARD_TELEGRAM_BASE_URL' => [
                        'label'       => 'Telegram Mini App URL',
                        'type'        => 'url',
                        'placeholder' => 'https://miniapp.example.com/',
                        'help'        => 'HTTPS URL configured in BotFather for the Mini App.',
                    ],
                    'TONBANKCARD_STAGING_BASE_URL'  => [
                        'label'       => 'Staging URL',
                        'type'        => 'url',
                        'placeholder' => 'https://staging-marketcap.example.com/',
                    ],
                    'TONBANKCARD_LOCAL_BASE_URL'    => [
                        'label'       => 'Local URL',
                        'type'        => 'url',
                        'placeholder' => 'http://localhost:8888/',
                    ],
                    'TONBANKCARD_DEBUG'             => [
                        'label' => 'Show PHP errors',
                        'type'  => 'boolean',
                        'help'  => 'Keep disabled for production unless a controlled troubleshooting window is active.',
                    ],
                    'TONBANKCARD_APP_MINIFIED'      => [
                        'label' => 'Use minified application bundle',
                        'type'  => 'boolean',
                    ],
                    'TONBANKCARD_PRECONNECT'        => [
                        'label' => 'Enable preconnect hints',
                        'type'  => 'boolean',
                    ],
                    'TONBANKCARD_CDN'               => [
                        'label' => 'Load vendor assets from CDN',
                        'type'  => 'boolean',
                    ],
                ],
            ],
            'telegram'     => [
                'title'       => 'Step 3. Telegram bot and Mini App entry points',
                'description' => 'Configure the bot identity, server-side bot token, webhook secret, and worker tokens used by Telegram flows.',
                'fields'      => [
                    'TONBANKCARD_BOT_USERNAME'       => [
                        'label'       => 'Bot username',
                        'type'        => 'text',
                        'placeholder' => 'tonbankcard_bot',
                        'help'        => 'Username only, without the @ prefix.',
                    ],
                    'TONBANKCARD_BOT_TOKEN'          => [
                        'label'       => 'Bot token',
                        'type'        => 'password',
                        'placeholder' => '123456:ABC...',
                        'secret'      => TRUE,
                    ],
                    'TONBANKCARD_BOT_WEBHOOK_SECRET' => [
                        'label'  => 'Webhook secret',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                    'TONBANKCARD_ALERT_WORKER_TOKEN' => [
                        'label'  => 'Alert worker token',
                        'type'   => 'password',
                        'secret' => TRUE,
                        'help'   => 'Leave blank to generate a safe token.',
                    ],
                    'TONBANKCARD_SEARCH_REFRESH_TOKEN' => [
                        'label'  => 'Search refresh token',
                        'type'   => 'password',
                        'secret' => TRUE,
                        'help'   => 'Leave blank to generate a safe token.',
                    ],
                ],
            ],
            'database'     => [
                'title'       => 'Step 4. MySQL or MariaDB database',
                'description' => 'Enter the hosting database credentials. The installer can test the connection and run all pending SQL migrations.',
                'fields'      => [
                    'MYSQL_HOST'     => [
                        'label'       => 'Database host',
                        'type'        => 'text',
                        'placeholder' => '127.0.0.1',
                        'help'        => 'Helper field used to build MYSQL_DSN.',
                    ],
                    'MYSQL_PORT'     => [
                        'label'       => 'Database port',
                        'type'        => 'number',
                        'placeholder' => '3306',
                        'help'        => 'Optional helper field.',
                    ],
                    'MYSQL_DATABASE' => [
                        'label'       => 'Database name',
                        'type'        => 'text',
                        'placeholder' => 'marketcap',
                        'help'        => 'Helper field used to build MYSQL_DSN.',
                    ],
                    'MYSQL_CHARSET'  => [
                        'label'       => 'Database charset',
                        'type'        => 'text',
                        'placeholder' => 'utf8mb4',
                        'help'        => 'Use utf8mb4 for production.',
                    ],
                    'MYSQL_DSN'      => [
                        'label'       => 'MYSQL_DSN',
                        'type'        => 'text',
                        'placeholder' => 'mysql:host=127.0.0.1;dbname=marketcap;charset=utf8mb4',
                    ],
                    'MYSQL_USER'     => [
                        'label'       => 'Database user',
                        'type'        => 'text',
                        'placeholder' => 'marketcap',
                    ],
                    'MYSQL_PASSWORD' => [
                        'label'  => 'Database password',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                ],
            ],
            'providers'    => [
                'title'       => 'Step 5. Providers, cache, and AI',
                'description' => 'Configure provider keys and cache services. Secrets stay in .env and are not rendered to the public app.',
                'fields'      => [
                    'COINGECKO_API_PLAN'         => [
                        'label'   => 'CoinGecko API plan',
                        'type'    => 'select',
                        'options' => [ 'demo', 'pro' ],
                    ],
                    'COINGECKO_API_KEY'          => [
                        'label'  => 'CoinGecko API key',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                    'UPSTASH_REDIS_REST_URL'     => [
                        'label'       => 'Upstash Redis REST URL',
                        'type'        => 'url',
                        'placeholder' => 'https://example.upstash.io',
                    ],
                    'UPSTASH_REDIS_REST_TOKEN'   => [
                        'label'  => 'Upstash Redis REST token',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                    'TONBANKCARD_AI_PROVIDER'    => [
                        'label'   => 'AI provider',
                        'type'    => 'select',
                        'options' => [ 'groq' ],
                    ],
                    'TONBANKCARD_AI_PROMPT_VERSION' => [
                        'label' => 'AI prompt version',
                        'type'  => 'text',
                    ],
                    'TONBANKCARD_AI_ENABLED_FEATURES' => [
                        'label' => 'AI enabled features',
                        'type'  => 'text',
                        'help'  => 'Comma-separated subset of summary, sentiment, insight.',
                    ],
                    'TONBANKCARD_AI_FALLBACK_BEHAVIOR' => [
                        'label'   => 'AI fallback behavior',
                        'type'    => 'select',
                        'options' => [ 'unavailable' ],
                    ],
                    'TONBANKCARD_SENTIMENT_CACHE_TTL_SECONDS' => [
                        'label' => 'Sentiment cache TTL seconds',
                        'type'  => 'number',
                    ],
                    'GROQ_API_KEY'               => [
                        'label'  => 'Groq API key',
                        'type'   => 'password',
                        'secret' => TRUE,
                    ],
                    'GROQ_MODEL_ID'              => [
                        'label' => 'Groq model id',
                        'type'  => 'text',
                    ],
                    'GROQ_BASE_URL'              => [
                        'label' => 'Groq base URL',
                        'type'  => 'url',
                    ],
                    'GROQ_TIMEOUT_SECONDS'       => [
                        'label' => 'Groq timeout seconds',
                        'type'  => 'number',
                    ],
                    'GROQ_RATE_LIMIT_WINDOW_SECONDS' => [
                        'label' => 'Groq rate-limit window seconds',
                        'type'  => 'number',
                    ],
                    'GROQ_RATE_LIMIT_MAX_REQUESTS' => [
                        'label' => 'Groq max requests per window',
                        'type'  => 'number',
                    ],
                    'CHANGENOW_LINK_ID'          => [
                        'label' => 'ChangeNOW link id',
                        'type'  => 'text',
                    ],
                ],
            ],
            'features'     => [
                'title'       => 'Step 6. Feature flags and product limits',
                'description' => 'Start with optional features disabled, then enable each feature after readiness checks pass.',
                'fields'      => [
                    'TONBANKCARD_FEATURE_AI'           => [ 'label' => 'AI insights', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_ALERTS'       => [ 'label' => 'Smart alerts', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_WIDGET'       => [ 'label' => 'Exchange widget alias', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_CHANGENOW'    => [ 'label' => 'ChangeNOW exchange widget', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_TON_CONNECT'  => [ 'label' => 'TON Connect', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_REFERRALS'    => [ 'label' => 'Referral attribution', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_GAMIFICATION' => [ 'label' => 'Achievements', 'type' => 'boolean' ],
                    'TONBANKCARD_FEATURE_PREMIUM'      => [ 'label' => 'Telegram Stars premium', 'type' => 'boolean' ],
                    'TONBANKCARD_ALERT_MAX_RULES_PER_USER' => [ 'label' => 'Alert max rules per user', 'type' => 'number' ],
                    'TONBANKCARD_ALERT_DEFAULT_FREQUENCY_CAP_SECONDS' => [ 'label' => 'Alert default frequency cap seconds', 'type' => 'number' ],
                    'TONBANKCARD_ALERT_MAX_DELIVERIES_PER_DAY' => [ 'label' => 'Alert max deliveries per day', 'type' => 'number' ],
                    'TONBANKCARD_ALERT_EVALUATION_INTERVAL_SECONDS' => [ 'label' => 'Alert evaluation interval seconds', 'type' => 'number' ],
                    'TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS' => [ 'label' => 'Achievement weekly check days', 'type' => 'number' ],
                    'TONBANKCARD_ACHIEVEMENT_SHARE_MILESTONE_COUNT' => [ 'label' => 'Achievement share milestone count', 'type' => 'number' ],
                    'TONBANKCARD_ACHIEVEMENT_MOVEMENT_THRESHOLD_PERCENT' => [ 'label' => 'Achievement movement threshold percent', 'type' => 'number' ],
                    'TONBANKCARD_ACHIEVEMENT_MAX_PROMPTS_PER_SESSION' => [ 'label' => 'Achievement max prompts per session', 'type' => 'number' ],
                    'TONBANKCARD_PREMIUM_PLAN_CODE' => [ 'label' => 'Premium plan code', 'type' => 'text' ],
                    'TONBANKCARD_PREMIUM_MONTHLY_STARS' => [ 'label' => 'Premium monthly Stars', 'type' => 'number' ],
                    'TONBANKCARD_PREMIUM_SUBSCRIPTION_PERIOD_SECONDS' => [ 'label' => 'Premium period seconds', 'type' => 'number' ],
                    'TONBANKCARD_PREMIUM_SIGNING_SECRET' => [ 'label' => 'Premium signing secret', 'type' => 'password', 'secret' => TRUE ],
                ],
            ],
            'operations'   => [
                'title'       => 'Step 7. Operations, admin, and performance budgets',
                'description' => 'Optional store paths, admin tokens, logging controls, readiness behavior, and performance budgets.',
                'fields'      => [
                    'TONBANKCARD_INSTALLER_ENABLED' => [
                        'label' => 'Installer enabled after .env exists',
                        'type'  => 'boolean',
                        'help'  => 'The saved .env sets this to false to lock the installer.',
                    ],
                    'TONBANKCARD_INSTALLER_TOKEN'   => [
                        'label'  => 'Installer re-entry token',
                        'type'   => 'password',
                        'secret' => TRUE,
                        'help'   => 'Required in the URL as ?token=... when re-enabling the installer.',
                    ],
                    'TONBANKCARD_API_AUDIT_LOG'     => [ 'label' => 'API audit log', 'type' => 'boolean' ],
                    'TONBANKCARD_API_ACTIVE_READINESS' => [ 'label' => 'Active readiness probes', 'type' => 'boolean' ],
                    'TONBANKCARD_OBSERVABILITY_LOG_LEVEL' => [
                        'label'   => 'Observability log level',
                        'type'    => 'select',
                        'options' => [ 'warning', 'debug', 'info', 'error', 'critical', 'off' ],
                    ],
                    'TONBANKCARD_VERBOSE_TRACING' => [ 'label' => 'Verbose tracing', 'type' => 'boolean' ],
                    'TONBANKCARD_CLIENT_ERROR_REPORTING' => [ 'label' => 'Client error reporting', 'type' => 'boolean' ],
                    'TONBANKCARD_TON_CURATION_FILE' => [ 'label' => 'TON curation file', 'type' => 'text' ],
                    'TONBANKCARD_TON_CURATION_TOKEN' => [ 'label' => 'TON curation token', 'type' => 'password', 'secret' => TRUE ],
                    'TONBANKCARD_ADMIN_STORE' => [ 'label' => 'Admin store path', 'type' => 'text' ],
                    'TONBANKCARD_ADMIN_TOKEN' => [ 'label' => 'Admin token', 'type' => 'password', 'secret' => TRUE ],
                    'TONBANKCARD_ADMIN_SUPPORT_TOKEN' => [ 'label' => 'Admin support token', 'type' => 'password', 'secret' => TRUE ],
                    'TONBANKCARD_CACHE_ENABLED' => [ 'label' => 'Cache enabled override', 'type' => 'boolean' ],
                    'TONBANKCARD_RATE_LIMIT_ENABLED' => [ 'label' => 'Rate limit enabled override', 'type' => 'boolean' ],
                    'TONBANKCARD_BUDGET_FIRST_CONTENTFUL_RENDER_MS' => [ 'label' => 'Budget first contentful render ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_APP_READY_MS' => [ 'label' => 'Budget app ready ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_MARKET_API_P95_MS' => [ 'label' => 'Budget market API p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_SEARCH_API_P95_MS' => [ 'label' => 'Budget search API p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_COIN_DETAIL_API_P95_MS' => [ 'label' => 'Budget coin detail API p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_CHART_RENDER_MS' => [ 'label' => 'Budget chart render ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_ALERT_DELIVERY_P95_MS' => [ 'label' => 'Budget alert delivery p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_BUDGET_SHARE_CARD_GENERATION_P95_MS' => [ 'label' => 'Budget share card generation p95 ms', 'type' => 'number' ],
                    'TONBANKCARD_STATIC_ASSET_IMMUTABLE_MAX_AGE_SECONDS' => [ 'label' => 'Immutable asset max-age seconds', 'type' => 'number' ],
                    'TONBANKCARD_STATIC_ASSET_UNVERSIONED_MAX_AGE_SECONDS' => [ 'label' => 'Unversioned asset max-age seconds', 'type' => 'number' ],
                    'TONBANKCARD_STATIC_ASSET_STALE_REVALIDATE_SECONDS' => [ 'label' => 'Asset stale-while-revalidate seconds', 'type' => 'number' ],
                    'TONBANKCARD_STATIC_ASSET_MANIFEST_MAX_AGE_SECONDS' => [ 'label' => 'Manifest max-age seconds', 'type' => 'number' ],
                ],
            ],
        ];
    }
}

if ( ! function_exists( 'tonbankcard_installer_field_definitions' ) ) {
    /**
     * @return array
     */
    function tonbankcard_installer_field_definitions() {
        $fields = [];
        foreach ( tonbankcard_installer_field_groups() as $group_key => $group ) {
            foreach ( $group['fields'] as $key => $definition ) {
                $definition['group'] = $group_key;
                if ( empty( $definition['label'] ) ) {
                    $definition['label'] = $key;
                }
                if ( empty( $definition['type'] ) ) {
                    $definition['type'] = 'text';
                }
                $fields[ $key ] = $definition;
            }
        }

        return $fields;
    }
}

if ( ! function_exists( 'tonbankcard_installer_field_keys' ) ) {
    /**
     * @param string|null $root
     * @return array
     */
    function tonbankcard_installer_field_keys( $root = null ) {
        $keys = array_keys( tonbankcard_installer_field_definitions() );
        $example = tonbankcard_installer_parse_env_file( tonbankcard_installer_env_example_path( $root ) );
        foreach ( array_keys( $example ) as $key ) {
            if ( ! in_array( $key, $keys, TRUE ) ) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}

if ( ! function_exists( 'tonbankcard_installer_env_keys' ) ) {
    /**
     * @param string|null $root
     * @return array
     */
    function tonbankcard_installer_env_keys( $root = null ) {
        $example = tonbankcard_installer_parse_env_file( tonbankcard_installer_env_example_path( $root ) );
        $keys = array_keys( $example );

        foreach ( [ 'TONBANKCARD_INSTALLER_ENABLED', 'TONBANKCARD_INSTALLER_TOKEN' ] as $key ) {
            if ( ! in_array( $key, $keys, TRUE ) ) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}

if ( ! function_exists( 'tonbankcard_installer_default_values' ) ) {
    /**
     * @param string|null $root
     * @return array
     */
    function tonbankcard_installer_default_values( $root = null ) {
        $root = null === $root ? tonbankcard_installer_root_dir() : rtrim( (string) $root, '/' );
        $values = tonbankcard_installer_parse_env_file( tonbankcard_installer_env_example_path( $root ) );
        $existing = tonbankcard_installer_parse_env_file( tonbankcard_installer_env_path( $root ) );
        $values = array_merge( $values, $existing );

        foreach ( tonbankcard_installer_field_keys( $root ) as $key ) {
            if ( ! array_key_exists( $key, $values ) ) {
                $values[ $key ] = '';
            }
        }

        if ( empty( $existing ) && isset( $values['TONBANKCARD_PROFILE'] ) && 'local' === $values['TONBANKCARD_PROFILE'] ) {
            $values['TONBANKCARD_PROFILE'] = 'production';
        }

        if ( empty( $values['MYSQL_HOST'] ) ) {
            $values['MYSQL_HOST'] = tonbankcard_installer_mysql_dsn_part( isset( $values['MYSQL_DSN'] ) ? $values['MYSQL_DSN'] : '', 'host' );
        }
        if ( empty( $values['MYSQL_PORT'] ) ) {
            $values['MYSQL_PORT'] = tonbankcard_installer_mysql_dsn_part( isset( $values['MYSQL_DSN'] ) ? $values['MYSQL_DSN'] : '', 'port' );
        }
        if ( empty( $values['MYSQL_DATABASE'] ) ) {
            $values['MYSQL_DATABASE'] = tonbankcard_installer_mysql_dsn_part( isset( $values['MYSQL_DSN'] ) ? $values['MYSQL_DSN'] : '', 'dbname' );
        }
        if ( empty( $values['MYSQL_CHARSET'] ) ) {
            $values['MYSQL_CHARSET'] = tonbankcard_installer_mysql_dsn_part( isset( $values['MYSQL_DSN'] ) ? $values['MYSQL_DSN'] : '', 'charset' );
        }
        if ( empty( $values['MYSQL_CHARSET'] ) ) {
            $values['MYSQL_CHARSET'] = 'utf8mb4';
        }

        return $values;
    }
}

if ( ! function_exists( 'tonbankcard_installer_mysql_dsn_part' ) ) {
    /**
     * @param string $dsn
     * @param string $part
     * @return string
     */
    function tonbankcard_installer_mysql_dsn_part( string $dsn, string $part ) {
        if ( 0 !== strpos( $dsn, 'mysql:' ) ) {
            return '';
        }

        $segments = explode( ';', substr( $dsn, 6 ) );
        foreach ( $segments as $segment ) {
            $separator = strpos( $segment, '=' );
            if ( FALSE === $separator ) {
                continue;
            }

            $key = trim( substr( $segment, 0, $separator ) );
            $value = trim( substr( $segment, $separator + 1 ) );
            if ( $key === $part ) {
                return $value;
            }
        }

        return '';
    }
}

if ( ! function_exists( 'tonbankcard_installer_build_mysql_dsn' ) ) {
    /**
     * @param string $host
     * @param string $port
     * @param string $database
     * @param string $charset
     * @return string
     */
    function tonbankcard_installer_build_mysql_dsn( string $host, string $port, string $database, string $charset = 'utf8mb4' ) {
        $host = trim( $host );
        $port = trim( $port );
        $database = trim( $database );
        $charset = trim( $charset );

        if ( '' === $host || '' === $database ) {
            return '';
        }

        $dsn = 'mysql:host=' . $host;
        if ( '' !== $port ) {
            $dsn .= ';port=' . $port;
        }
        $dsn .= ';dbname=' . $database;
        if ( '' !== $charset ) {
            $dsn .= ';charset=' . $charset;
        }

        return $dsn;
    }
}

if ( ! function_exists( 'tonbankcard_installer_generate_token' ) ) {
    /**
     * @return string
     */
    function tonbankcard_installer_generate_token() {
        try {
            return bin2hex( random_bytes( 24 ) );
        } catch ( Exception $exception ) {
            return hash( 'sha256', uniqid( 'tonbankcard-installer-', TRUE ) . mt_rand() );
        }
    }
}

if ( ! function_exists( 'tonbankcard_installer_prepare_values' ) ) {
    /**
     * Normalizes submitted values and derives helper values before saving.
     *
     * @param array $input
     * @param string|null $root
     * @return array
     */
    function tonbankcard_installer_prepare_values( array $input, $root = null ) {
        $values = tonbankcard_installer_default_values( $root );
        foreach ( tonbankcard_installer_field_keys( $root ) as $key ) {
            if ( array_key_exists( $key, $input ) ) {
                $values[ $key ] = is_array( $input[ $key ] ) ? '' : trim( (string) $input[ $key ] );
            }
        }

        if ( empty( $values['MYSQL_DSN'] ) ) {
            $values['MYSQL_DSN'] = tonbankcard_installer_build_mysql_dsn(
                isset( $values['MYSQL_HOST'] ) ? $values['MYSQL_HOST'] : '',
                isset( $values['MYSQL_PORT'] ) ? $values['MYSQL_PORT'] : '',
                isset( $values['MYSQL_DATABASE'] ) ? $values['MYSQL_DATABASE'] : '',
                isset( $values['MYSQL_CHARSET'] ) ? $values['MYSQL_CHARSET'] : 'utf8mb4'
            );
        }

        foreach ( [ 'TONBANKCARD_ALERT_WORKER_TOKEN', 'TONBANKCARD_SEARCH_REFRESH_TOKEN' ] as $key ) {
            if ( array_key_exists( $key, $values ) && '' === trim( (string) $values[ $key ] ) ) {
                $values[ $key ] = tonbankcard_installer_generate_token();
            }
        }

        $values['TONBANKCARD_INSTALLER_ENABLED'] = 'false';

        foreach ( tonbankcard_installer_boolean_keys() as $key ) {
            if ( array_key_exists( $key, $values ) ) {
                $values[ $key ] = tonbankcard_installer_normalize_bool( $values[ $key ], $values[ $key ] );
            }
        }

        return $values;
    }
}

if ( ! function_exists( 'tonbankcard_installer_boolean_keys' ) ) {
    /**
     * @return array
     */
    function tonbankcard_installer_boolean_keys() {
        $keys = [];
        foreach ( tonbankcard_installer_field_definitions() as $key => $definition ) {
            if ( isset( $definition['type'] ) && 'boolean' === $definition['type'] ) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}

if ( ! function_exists( 'tonbankcard_installer_normalize_bool' ) ) {
    /**
     * @param mixed $value
     * @param mixed $default
     * @return string
     */
    function tonbankcard_installer_normalize_bool( $value, $default = 'false' ) {
        if ( is_bool( $value ) ) {
            return $value ? 'true' : 'false';
        }

        $normalized = strtolower( trim( (string) $value ) );
        if ( in_array( $normalized, [ '1', 'true', 'yes', 'on' ], TRUE ) ) {
            return 'true';
        }
        if ( in_array( $normalized, [ '0', 'false', 'no', 'off', '' ], TRUE ) ) {
            return 'false';
        }

        return tonbankcard_installer_normalize_bool( $default, 'false' );
    }
}

if ( ! function_exists( 'tonbankcard_installer_format_env_value' ) ) {
    /**
     * @param mixed $value
     * @return string
     */
    function tonbankcard_installer_format_env_value( $value ) {
        $value = (string) $value;
        if ( '' === $value ) {
            return '';
        }

        if ( preg_match( '/^[A-Za-z0-9._:\/@%+,;=?-]+$/', $value ) && FALSE === strpos( $value, '#' ) ) {
            return $value;
        }

        return '"' . str_replace( [ '\\', '"' ], [ '\\\\', '\\"' ], $value ) . '"';
    }
}

if ( ! function_exists( 'tonbankcard_installer_render_env' ) ) {
    /**
     * @param array $values
     * @param string|null $root
     * @return string
     */
    function tonbankcard_installer_render_env( array $values, $root = null ) {
        $prepared = tonbankcard_installer_prepare_values( $values, $root );
        $lines = [
            '# Generated by the TONBANKCARD automatic hosting installer.',
            '# Keep this file private. The Apache .htaccess blocks dotfile downloads.',
        ];

        foreach ( tonbankcard_installer_env_keys( $root ) as $key ) {
            $value = array_key_exists( $key, $prepared ) ? $prepared[ $key ] : '';
            $lines[] = $key . '=' . tonbankcard_installer_format_env_value( $value );
        }

        return implode( "\n", $lines ) . "\n";
    }
}

if ( ! function_exists( 'tonbankcard_installer_write_env' ) ) {
    /**
     * @param string $root
     * @param array $values
     * @return array
     */
    function tonbankcard_installer_write_env( string $root, array $values ) {
        $path = tonbankcard_installer_env_path( $root );
        $env = tonbankcard_installer_render_env( $values, $root );
        $backup = null;

        if ( is_file( $path ) ) {
            $backup = $path . '.' . gmdate( 'YmdHis' ) . '.bak';
            if ( ! copy( $path, $backup ) ) {
                return [
                    'ok'      => FALSE,
                    'message' => 'Could not create a backup of the existing .env file.',
                    'env'     => $env,
                ];
            }
        }

        if ( FALSE === file_put_contents( $path, $env, LOCK_EX ) ) {
            return [
                'ok'      => FALSE,
                'message' => 'Could not write .env. Check write permissions on the repository root.',
                'env'     => $env,
            ];
        }

        @chmod( $path, 0600 );

        return [
            'ok'      => TRUE,
            'message' => 'The .env file was written and the installer was locked.',
            'path'    => $path,
            'backup'  => $backup,
            'env'     => $env,
        ];
    }
}

if ( ! function_exists( 'tonbankcard_installer_is_secret_key' ) ) {
    /**
     * @param string $key
     * @return bool
     */
    function tonbankcard_installer_is_secret_key( string $key ) {
        if ( in_array( $key, [ 'TONBANKCARD_BOT_USERNAME' ], TRUE ) ) {
            return FALSE;
        }

        return (bool) preg_match( '/(PASSWORD|TOKEN|SECRET|API_KEY|SIGNING_SECRET)$/', $key );
    }
}

if ( ! function_exists( 'tonbankcard_installer_display_value' ) ) {
    /**
     * @param string $key
     * @param mixed $value
     * @return string
     */
    function tonbankcard_installer_display_value( string $key, $value ) {
        $value = (string) $value;
        if ( '' === trim( $value ) ) {
            return '';
        }

        if ( tonbankcard_installer_is_secret_key( $key ) ) {
            return 'configured';
        }

        return $value;
    }
}

if ( ! function_exists( 'tonbankcard_installer_value_enabled' ) ) {
    /**
     * @param array $values
     * @param string $key
     * @return bool
     */
    function tonbankcard_installer_value_enabled( array $values, string $key ) {
        return isset( $values[ $key ] ) && 'true' === tonbankcard_installer_normalize_bool( $values[ $key ] );
    }
}

if ( ! function_exists( 'tonbankcard_installer_valid_url' ) ) {
    /**
     * @param string $value
     * @return bool
     */
    function tonbankcard_installer_valid_url( string $value ) {
        $parts = parse_url( trim( $value ) );
        return is_array( $parts )
            && ! empty( $parts['scheme'] )
            && in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], TRUE )
            && ! empty( $parts['host'] );
    }
}

if ( ! function_exists( 'tonbankcard_installer_validate_values' ) ) {
    /**
     * Validates high-risk runtime values before writing .env.
     *
     * @param array $values
     * @return array
     */
    function tonbankcard_installer_validate_values( array $values ) {
        $prepared = tonbankcard_installer_prepare_values( $values );
        $errors = [];
        $profile = isset( $prepared['TONBANKCARD_PROFILE'] ) ? $prepared['TONBANKCARD_PROFILE'] : 'production';

        if ( ! in_array( $profile, [ 'local', 'staging', 'production', 'telegram' ], TRUE ) ) {
            $errors[] = 'TONBANKCARD_PROFILE must be local, staging, production, or telegram.';
        }

        $required_urls = [];
        if ( 'local' === $profile ) {
            $required_urls['TONBANKCARD_LOCAL_BASE_URL'] = 'local base URL';
        } elseif ( 'staging' === $profile ) {
            $required_urls['TONBANKCARD_STAGING_BASE_URL'] = 'staging URL';
        } elseif ( 'production' === $profile ) {
            $required_urls['TONBANKCARD_PUBLIC_BASE_URL'] = 'public website URL';
        } elseif ( 'telegram' === $profile ) {
            $required_urls['TONBANKCARD_PUBLIC_BASE_URL'] = 'public website URL';
            $required_urls['TONBANKCARD_TELEGRAM_BASE_URL'] = 'Telegram Mini App URL';
        }

        foreach ( $required_urls as $key => $label ) {
            if ( empty( $prepared[ $key ] ) || ! tonbankcard_installer_valid_url( $prepared[ $key ] ) ) {
                $errors[] = $key . ' must be a valid absolute HTTP(S) ' . $label . '.';
            }
        }

        if ( 'local' !== $profile ) {
            foreach ( [
                'TONBANKCARD_BOT_USERNAME'     => 'Telegram bot username',
                'UPSTASH_REDIS_REST_URL'       => 'Upstash Redis REST URL',
                'UPSTASH_REDIS_REST_TOKEN'     => 'Upstash Redis REST token',
                'MYSQL_DSN'                    => 'MySQL DSN',
                'MYSQL_USER'                   => 'MySQL user',
                'MYSQL_PASSWORD'               => 'MySQL password',
            ] as $key => $label ) {
                if ( empty( $prepared[ $key ] ) ) {
                    $errors[] = $key . ' is required for non-local installs (' . $label . ').';
                }
            }
        }

        if ( ! empty( $prepared['UPSTASH_REDIS_REST_URL'] ) && ! tonbankcard_installer_valid_url( $prepared['UPSTASH_REDIS_REST_URL'] ) ) {
            $errors[] = 'UPSTASH_REDIS_REST_URL must be a valid absolute HTTP(S) URL.';
        }

        if ( ! empty( $prepared['MYSQL_DSN'] ) && 0 !== strpos( $prepared['MYSQL_DSN'], 'mysql:' ) ) {
            $errors[] = 'MYSQL_DSN must start with mysql:.';
        }

        if ( isset( $prepared['COINGECKO_API_PLAN'] ) && 'pro' === strtolower( (string) $prepared['COINGECKO_API_PLAN'] ) && empty( $prepared['COINGECKO_API_KEY'] ) ) {
            $errors[] = 'COINGECKO_API_KEY is required when COINGECKO_API_PLAN is pro.';
        }

        if ( 'telegram' === $profile || tonbankcard_installer_value_enabled( $prepared, 'TONBANKCARD_FEATURE_ALERTS' ) ) {
            if ( empty( $prepared['TONBANKCARD_BOT_TOKEN'] ) ) {
                $errors[] = 'TONBANKCARD_BOT_TOKEN is required for the telegram profile or alerts.';
            }
        }

        if ( tonbankcard_installer_value_enabled( $prepared, 'TONBANKCARD_FEATURE_AI' ) && empty( $prepared['GROQ_API_KEY'] ) ) {
            $errors[] = 'GROQ_API_KEY is required when TONBANKCARD_FEATURE_AI is true.';
        }

        if ( tonbankcard_installer_value_enabled( $prepared, 'TONBANKCARD_FEATURE_CHANGENOW' ) && empty( $prepared['CHANGENOW_LINK_ID'] ) ) {
            $errors[] = 'CHANGENOW_LINK_ID is required when TONBANKCARD_FEATURE_CHANGENOW is true.';
        }

        return $errors;
    }
}

if ( ! function_exists( 'tonbankcard_installer_lock_state' ) ) {
    /**
     * Determines whether the installer can run.
     *
     * @param string $root
     * @param array $query
     * @return array
     */
    function tonbankcard_installer_lock_state( string $root, array $query ) {
        $path = tonbankcard_installer_env_path( $root );
        if ( ! is_file( $path ) ) {
            return [
                'allowed'   => TRUE,
                'first_run' => TRUE,
                'locked'    => FALSE,
                'message'   => 'Installer is available because no .env file exists yet.',
            ];
        }

        $env = tonbankcard_installer_parse_env_file( $path );
        $enabled = isset( $env['TONBANKCARD_INSTALLER_ENABLED'] )
            ? tonbankcard_installer_normalize_bool( $env['TONBANKCARD_INSTALLER_ENABLED'] )
            : 'false';

        if ( 'true' !== $enabled ) {
            return [
                'allowed'   => FALSE,
                'first_run' => FALSE,
                'locked'    => TRUE,
                'message'   => 'Installer is locked because .env exists and TONBANKCARD_INSTALLER_ENABLED is not true.',
            ];
        }

        $token = isset( $env['TONBANKCARD_INSTALLER_TOKEN'] ) ? trim( (string) $env['TONBANKCARD_INSTALLER_TOKEN'] ) : '';
        if ( '' === $token ) {
            return [
                'allowed'        => FALSE,
                'first_run'      => FALSE,
                'locked'         => TRUE,
                'token_required' => TRUE,
                'message'        => 'Set TONBANKCARD_INSTALLER_TOKEN before re-opening the installer.',
            ];
        }

        $provided = isset( $query['token'] ) ? trim( (string) $query['token'] ) : '';
        if ( hash_equals( $token, $provided ) ) {
            return [
                'allowed'     => TRUE,
                'first_run'   => FALSE,
                'locked'      => FALSE,
                'token_valid' => TRUE,
                'message'     => 'Installer token accepted.',
            ];
        }

        return [
            'allowed'        => FALSE,
            'first_run'      => FALSE,
            'locked'         => TRUE,
            'token_required' => TRUE,
            'message'        => 'Installer is locked. Add the configured token to the URL as ?token=...',
        ];
    }
}

if ( ! function_exists( 'tonbankcard_installer_system_checks' ) ) {
    /**
     * @param string $root
     * @return array
     */
    function tonbankcard_installer_system_checks( string $root ) {
        $extensions = [ 'pdo', 'pdo_mysql', 'json', 'hash', 'openssl', 'filter', 'session' ];
        $checks = [
            [
                'name'    => 'PHP version',
                'status'  => version_compare( PHP_VERSION, '8.1.0', '>=' ) ? 'ok' : 'fail',
                'message' => 'Running PHP ' . PHP_VERSION . '; PHP 8.1+ is required.',
            ],
        ];

        foreach ( $extensions as $extension ) {
            $checks[] = [
                'name'    => 'PHP extension ' . $extension,
                'status'  => extension_loaded( $extension ) ? 'ok' : 'fail',
                'message' => extension_loaded( $extension ) ? 'Loaded.' : 'Enable this extension in the hosting PHP selector.',
            ];
        }

        $env_path = tonbankcard_installer_env_path( $root );
        $checks[] = [
            'name'    => '.env write access',
            'status'  => ( is_file( $env_path ) && is_writable( $env_path ) ) || ( ! is_file( $env_path ) && is_writable( $root ) ) ? 'ok' : 'warn',
            'message' => 'The installer needs write access to create or replace .env. If this is not available, use the generated .env preview.',
        ];

        $checks[] = [
            'name'    => 'HTTPS request',
            'status'  => ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) ) ? 'ok' : 'warn',
            'message' => 'Production and Telegram Mini App deployments must use HTTPS.',
        ];

        return $checks;
    }
}

if ( ! function_exists( 'tonbankcard_installer_database_connection' ) ) {
    /**
     * @param array $values
     * @return PDO
     */
    function tonbankcard_installer_database_connection( array $values ) {
        if ( ! class_exists( 'PDO' ) ) {
            throw new RuntimeException( 'PDO is not available.' );
        }

        $prepared = tonbankcard_installer_prepare_values( $values );
        $dsn = isset( $prepared['MYSQL_DSN'] ) ? trim( (string) $prepared['MYSQL_DSN'] ) : '';
        $user = isset( $prepared['MYSQL_USER'] ) ? (string) $prepared['MYSQL_USER'] : '';
        $password = isset( $prepared['MYSQL_PASSWORD'] ) ? (string) $prepared['MYSQL_PASSWORD'] : '';

        if ( '' === $dsn || '' === $user ) {
            throw new RuntimeException( 'MYSQL_DSN and MYSQL_USER are required before testing the database.' );
        }

        return new PDO(
            $dsn,
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}

if ( ! function_exists( 'tonbankcard_installer_test_database' ) ) {
    /**
     * @param array $values
     * @return array
     */
    function tonbankcard_installer_test_database( array $values ) {
        try {
            $pdo = tonbankcard_installer_database_connection( $values );
            $pdo->query( 'SELECT 1' );

            return [
                'ok'      => TRUE,
                'message' => 'Database connection succeeded.',
            ];
        } catch ( Throwable $error ) {
            return [
                'ok'      => FALSE,
                'message' => 'Database connection failed: ' . $error->getMessage(),
            ];
        }
    }
}

if ( ! function_exists( 'tonbankcard_installer_migration_files' ) ) {
    /**
     * @param string $dir
     * @return array
     */
    function tonbankcard_installer_migration_files( string $dir ) {
        if ( ! is_dir( $dir ) ) {
            throw new RuntimeException( 'Migration directory does not exist: ' . $dir );
        }

        $files = glob( rtrim( $dir, '/' ) . '/*.up.sql' );
        if ( ! is_array( $files ) ) {
            $files = [];
        }
        sort( $files, SORT_NATURAL );

        $migrations = [];
        foreach ( $files as $file ) {
            $version = basename( $file, '.up.sql' );
            $migrations[ $version ] = $file;
        }

        return $migrations;
    }
}

if ( ! function_exists( 'tonbankcard_installer_migration_plan' ) ) {
    /**
     * @param string $dir
     * @param array $applied_versions
     * @return array
     */
    function tonbankcard_installer_migration_plan( string $dir, array $applied_versions = [] ) {
        $applied = array_fill_keys( $applied_versions, TRUE );
        $plan = [];

        foreach ( tonbankcard_installer_migration_files( $dir ) as $version => $file ) {
            $plan[] = [
                'version' => $version,
                'file'    => $file,
                'status'  => isset( $applied[ $version ] ) ? 'applied' : 'pending',
            ];
        }

        return $plan;
    }
}

if ( ! function_exists( 'tonbankcard_installer_ensure_migration_ledger' ) ) {
    /**
     * @param PDO $pdo
     * @return void
     */
    function tonbankcard_installer_ensure_migration_ledger( PDO $pdo ) {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `version` VARCHAR(191) NOT NULL,
                `description` VARCHAR(255) NOT NULL,
                `checksum` CHAR(64) NOT NULL,
                `applied_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
                PRIMARY KEY (`version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}

if ( ! function_exists( 'tonbankcard_installer_applied_migrations' ) ) {
    /**
     * @param PDO $pdo
     * @return array
     */
    function tonbankcard_installer_applied_migrations( PDO $pdo ) {
        tonbankcard_installer_ensure_migration_ledger( $pdo );
        $rows = $pdo->query( 'SELECT version FROM schema_migrations ORDER BY version' )->fetchAll();
        $versions = [];
        foreach ( $rows as $row ) {
            if ( isset( $row['version'] ) ) {
                $versions[] = $row['version'];
            }
        }

        return $versions;
    }
}

if ( ! function_exists( 'tonbankcard_installer_sql_statements' ) ) {
    /**
     * @param string $sql
     * @return array
     */
    function tonbankcard_installer_sql_statements( string $sql ) {
        $parts = preg_split( '/;\s*(?:\r?\n|$)/', $sql );
        if ( ! is_array( $parts ) ) {
            return [];
        }

        $statements = [];
        foreach ( $parts as $part ) {
            $statement = trim( $part );
            if ( '' !== $statement ) {
                $statements[] = $statement;
            }
        }

        return $statements;
    }
}

if ( ! function_exists( 'tonbankcard_installer_migration_description' ) ) {
    /**
     * @param string $version
     * @return string
     */
    function tonbankcard_installer_migration_description( string $version ) {
        $description = preg_replace( '/^[0-9]+_?/', '', $version );
        $description = str_replace( '_', ' ', (string) $description );

        return trim( $description ) ?: $version;
    }
}

if ( ! function_exists( 'tonbankcard_installer_apply_migrations' ) ) {
    /**
     * @param array $values
     * @param string $dir
     * @return array
     */
    function tonbankcard_installer_apply_migrations( array $values, string $dir ) {
        $pdo = tonbankcard_installer_database_connection( $values );
        tonbankcard_installer_ensure_migration_ledger( $pdo );
        $applied = array_fill_keys( tonbankcard_installer_applied_migrations( $pdo ), TRUE );
        $applied_now = [];

        foreach ( tonbankcard_installer_migration_files( $dir ) as $version => $file ) {
            if ( isset( $applied[ $version ] ) ) {
                continue;
            }

            $sql = file_get_contents( $file );
            if ( FALSE === $sql ) {
                throw new RuntimeException( 'Cannot read migration file: ' . $file );
            }

            foreach ( tonbankcard_installer_sql_statements( $sql ) as $statement ) {
                $pdo->exec( $statement );
            }

            $insert = $pdo->prepare(
                'INSERT INTO schema_migrations (version, description, checksum) VALUES (:version, :description, :checksum)'
            );
            $insert->execute(
                [
                    ':version'     => $version,
                    ':description' => tonbankcard_installer_migration_description( $version ),
                    ':checksum'    => hash_file( 'sha256', $file ),
                ]
            );

            $applied_now[] = $version;
        }

        return [
            'ok'       => TRUE,
            'applied'  => $applied_now,
            'message'  => empty( $applied_now ) ? 'No pending migrations.' : 'Applied ' . count( $applied_now ) . ' migration(s).',
        ];
    }
}
