<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 GAMIFICATION ACHIEVEMENTS API
 * -------------------------------------------------------------------------
 * Public, secret-free achievement definitions and tuning knobs for the
 * browser-owned gamification experience.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when the path belongs to the achievements API surface.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_achievements_is_request( string $path ) {
    $path = tonbankcard_api_normalize_path( $path );
    return '/api/achievements' === $path || 0 === strpos( $path, '/api/achievements/' );
}

/**
 * Returns documented achievements API routes.
 *
 * @return array
 */
function tonbankcard_api_achievements_routes() {
    return [
        '/api/achievements',
        '/api/achievements/settings',
    ];
}

/**
 * Handles achievements API requests.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_achievements_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );

    if ( ! in_array( $path, tonbankcard_api_achievements_routes(), TRUE ) ) {
        return tonbankcard_api_error_response(
            404,
            'not_found',
            'No achievements API route matches the request.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    if ( 'GET' !== strtoupper( $request['method'] ) ) {
        return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
    }

    return tonbankcard_api_success_response(
        tonbankcard_api_achievements_public_payload( $runtime, $config ),
        $request_id,
        $headers,
        200,
        [ 'route_group' => 'achievements' ]
    );
}

/**
 * Builds the public achievements payload. This never includes secrets.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_achievements_public_payload( array $runtime, array $config ) {
    $settings = tonbankcard_api_achievements_settings( $runtime, $config );

    return [
        'service'        => 'tonbankcard-achievements',
        'settings'       => $settings,
        'definitions'    => tonbankcard_api_achievements_definitions( $settings ),
        'admin_controls' => [
            'feature_flag'        => 'gamification',
            'feature_flags_table' => 'feature_flags',
            'tunable_env'         => [
                'TONBANKCARD_FEATURE_GAMIFICATION'                => 'Enable or disable the feature globally.',
                'TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS'       => 'Consecutive local market-check days required for the weekly market check badge.',
                'TONBANKCARD_ACHIEVEMENT_SHARE_MILESTONE_COUNT'   => 'Share starts required for the share milestone badge.',
                'TONBANKCARD_ACHIEVEMENT_MOVEMENT_THRESHOLD_PERCENT' => 'Absolute 24h move percent that qualifies as a caught movement.',
                'TONBANKCARD_ACHIEVEMENT_MAX_PROMPTS_PER_SESSION' => 'Maximum achievement prompts shown at once.',
            ],
        ],
    ];
}

/**
 * Returns clamped public settings for the achievement engine.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_achievements_settings( array $runtime, array $config ) {
    $source = isset( $config['achievements'] ) && is_array( $config['achievements'] ) ? $config['achievements'] : [];
    $features = isset( $runtime['feature_flags'] ) && is_array( $runtime['feature_flags'] ) ? $runtime['feature_flags'] : [];

    return [
        'enabled'                    => ! empty( $features['gamification'] ),
        'definitions_version'        => 'v1',
        'storage_key'                => 'TONBANKCARD:achievements:v1',
        'weekly_check_days'          => tonbankcard_api_achievements_clamp_int( $source, 'weekly_check_days', 7, 2, 30 ),
        'share_milestone_count'      => tonbankcard_api_achievements_clamp_int( $source, 'share_milestone_count', 3, 1, 100 ),
        'movement_threshold_percent' => tonbankcard_api_achievements_clamp_float( $source, 'movement_threshold_percent', 7.5, 1.0, 100.0 ),
        'max_prompts_per_session'    => tonbankcard_api_achievements_clamp_int( $source, 'max_prompts_per_session', 1, 1, 6 ),
        'prompt_cooldown_hours'      => tonbankcard_api_achievements_clamp_int( $source, 'prompt_cooldown_hours', 24, 1, 168 ),
        'haptics_enabled'            => array_key_exists( 'haptics_enabled', $source ) ? (bool) $source['haptics_enabled'] : TRUE,
    ];
}

/**
 * Returns achievement definitions used by browser and docs.
 *
 * @param array $settings
 * @return array
 */
function tonbankcard_api_achievements_definitions( array $settings = [] ) {
    $weekly_days = isset( $settings['weekly_check_days'] ) ? (int) $settings['weekly_check_days'] : 7;
    $share_count = isset( $settings['share_milestone_count'] ) ? (int) $settings['share_milestone_count'] : 3;
    $movement_threshold = isset( $settings['movement_threshold_percent'] ) ? (float) $settings['movement_threshold_percent'] : 7.5;

    return [
        [
            'id'          => 'first_watchlist',
            'title'       => 'First watchlist',
            'description' => 'Save the first market asset to a watchlist.',
            'trigger'     => 'watchlist_added',
            'threshold'   => 1,
            'category'    => 'useful_setup',
            'badge_icon'  => 'mdi-star-check-outline',
        ],
        [
            'id'          => 'first_alert',
            'title'       => 'First alert',
            'description' => 'Create the first smart alert rule.',
            'trigger'     => 'alert_created',
            'threshold'   => 1,
            'category'    => 'useful_setup',
            'badge_icon'  => 'mdi-bell-check-outline',
        ],
        [
            'id'          => 'weekly_market_check',
            'title'       => 'Weekly market check',
            'description' => 'Check market pulse on consecutive local days.',
            'trigger'     => 'market_check',
            'threshold'   => $weekly_days,
            'category'    => 'streak',
            'badge_icon'  => 'mdi-calendar-week-outline',
        ],
        [
            'id'          => 'ton_explorer',
            'title'       => 'TON explorer',
            'description' => 'Open TON ecosystem market context.',
            'trigger'     => 'ton_viewed',
            'threshold'   => 1,
            'category'    => 'ton_ecosystem',
            'badge_icon'  => 'mdi-diamond-stone',
        ],
        [
            'id'          => 'share_milestone',
            'title'       => 'Share milestone',
            'description' => 'Share useful market context.',
            'trigger'     => 'share_started',
            'threshold'   => $share_count,
            'category'    => 'sharing',
            'badge_icon'  => 'mdi-share-variant-outline',
        ],
        [
            'id'          => 'caught_market_movement',
            'title'       => 'Caught market movement',
            'description' => 'Open a market view with a qualifying 24h move.',
            'trigger'     => 'market_movement_caught',
            'threshold'   => $movement_threshold,
            'category'    => 'market_awareness',
            'badge_icon'  => 'mdi-trending-up',
        ],
    ];
}

/**
 * Calculates local-day streaks from event timestamps.
 *
 * @param array $events
 * @param string $timezone
 * @param int|null $now
 * @return array
 */
function tonbankcard_api_achievements_calculate_streak( array $events, string $timezone = 'UTC', $now = null ) {
    try {
        $tz = new DateTimeZone( '' !== trim( $timezone ) ? $timezone : 'UTC' );
    } catch ( Exception $exception ) {
        $tz = new DateTimeZone( 'UTC' );
    }

    $now_timestamp = is_numeric( $now ) ? (int) $now : time();
    $now_local_date = ( new DateTimeImmutable( '@' . $now_timestamp ) )->setTimezone( $tz )->format( 'Y-m-d' );
    $dates = [];

    foreach ( $events as $event ) {
        $occurred_at = is_array( $event ) && isset( $event['occurred_at'] ) ? $event['occurred_at'] : $event;
        if ( is_numeric( $occurred_at ) ) {
            $timestamp = (int) $occurred_at;
        } else {
            $timestamp = strtotime( (string) $occurred_at );
        }

        if ( FALSE === $timestamp || $timestamp > $now_timestamp + 60 ) {
            continue;
        }

        $local_date = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $tz )->format( 'Y-m-d' );
        if ( $local_date <= $now_local_date ) {
            $dates[ $local_date ] = TRUE;
        }
    }

    ksort( $dates );
    $local_dates = array_keys( $dates );
    $max_count = 0;
    $current_count = 0;
    $previous = null;

    foreach ( $local_dates as $local_date ) {
        if ( null === $previous ) {
            $current_count = 1;
        } else {
            $previous_date = new DateTimeImmutable( $previous . ' 00:00:00', $tz );
            $current_date = new DateTimeImmutable( $local_date . ' 00:00:00', $tz );
            $days = (int) $previous_date->diff( $current_date )->format( '%r%a' );
            $current_count = 1 === $days ? $current_count + 1 : 1;
        }

        if ( $current_count > $max_count ) {
            $max_count = $current_count;
        }
        $previous = $local_date;
    }

    return [
        'timezone'        => $tz->getName(),
        'current_count'   => empty( $local_dates ) ? 0 : $current_count,
        'max_count'       => $max_count,
        'last_local_date' => empty( $local_dates ) ? null : $local_dates[ count( $local_dates ) - 1 ],
        'local_dates'     => $local_dates,
    ];
}

/**
 * Returns a clamped integer setting.
 *
 * @param array $source
 * @param string $key
 * @param int $default
 * @param int $min
 * @param int $max
 * @return int
 */
function tonbankcard_api_achievements_clamp_int( array $source, string $key, int $default, int $min, int $max ) {
    $value = isset( $source[ $key ] ) && is_numeric( $source[ $key ] ) ? (int) $source[ $key ] : $default;
    return max( $min, min( $max, $value ) );
}

/**
 * Returns a clamped float setting.
 *
 * @param array $source
 * @param string $key
 * @param float $default
 * @param float $min
 * @param float $max
 * @return float
 */
function tonbankcard_api_achievements_clamp_float( array $source, string $key, float $default, float $min, float $max ) {
    $value = isset( $source[ $key ] ) && is_numeric( $source[ $key ] ) ? (float) $source[ $key ] : $default;
    return max( $min, min( $max, $value ) );
}
