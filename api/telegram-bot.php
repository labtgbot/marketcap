<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 TELEGRAM BOT COMPANION API
 * -------------------------------------------------------------------------
 * Webhook command and inline-mode response builder for the Telegram bot. The
 * valid webhook path returns Telegram Bot API method payloads directly so
 * Telegram can deliver a response in the same webhook round trip.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when the normalized path belongs to the Telegram bot API.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_telegram_bot_is_request( string $path ) {
    $path = tonbankcard_api_normalize_path( $path );
    return '/api/telegram/bot' === $path || '/api/telegram/bot/commands' === $path;
}

/**
 * Returns documented Telegram bot routes.
 *
 * @return array
 */
function tonbankcard_api_telegram_bot_routes() {
    return [
        '/api/telegram/bot',
        '/api/telegram/bot/commands',
    ];
}

/**
 * Handles Telegram bot API requests.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_telegram_bot_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );
    $method = strtoupper( $request['method'] );

    if ( '/api/telegram/bot/commands' === $path ) {
        if ( 'GET' !== $method ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            tonbankcard_api_telegram_bot_commands_payload(),
            $request_id,
            $headers
        );
    }

    if ( '/api/telegram/bot' !== $path ) {
        return tonbankcard_api_error_response(
            404,
            'not_found',
            'No Telegram bot route matches the request.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    if ( 'POST' !== $method ) {
        return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
    }

    $settings = tonbankcard_api_telegram_bot_settings( $runtime, $config );
    if ( ! tonbankcard_api_telegram_bot_secret_allowed( $request, $settings ) ) {
        return tonbankcard_api_telegram_bot_error_response(
            401,
            'telegram_bot_unauthorized',
            'Telegram bot webhook secret validation failed.',
            [ 'header' => 'X-Telegram-Bot-Api-Secret-Token' ],
            $request_id,
            $runtime,
            $config,
            $headers
        );
    }

    $update = tonbankcard_api_json_body( $request );
    if ( empty( $update ) ) {
        return tonbankcard_api_telegram_bot_error_response(
            400,
            'telegram_bot_invalid_update',
            'Telegram bot webhook update must be a JSON object.',
            [ 'field' => 'body' ],
            $request_id,
            $runtime,
            $config,
            $headers
        );
    }

    $result = tonbankcard_api_telegram_bot_response_for_update( $update, $runtime, $config, $settings, $request_id );
    if ( empty( $result['ok'] ) ) {
        return tonbankcard_api_telegram_bot_error_response(
            isset( $result['status'] ) ? (int) $result['status'] : 400,
            isset( $result['code'] ) ? (string) $result['code'] : 'telegram_bot_invalid_update',
            isset( $result['message'] ) ? (string) $result['message'] : 'Telegram bot webhook update could not be processed.',
            isset( $result['details'] ) && is_array( $result['details'] ) ? $result['details'] : [],
            $request_id,
            $runtime,
            $config,
            $headers
        );
    }

    return tonbankcard_api_telegram_bot_json_response(
        isset( $result['payload'] ) && is_array( $result['payload'] ) ? $result['payload'] : [],
        $headers
    );
}

/**
 * Returns normalized Telegram bot settings.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_telegram_bot_settings( array $runtime, array $config ) {
    $bot = isset( $config['telegram_bot'] ) && is_array( $config['telegram_bot'] ) ? $config['telegram_bot'] : [];
    $runtime_telegram = isset( $runtime['telegram'] ) && is_array( $runtime['telegram'] ) ? $runtime['telegram'] : [];

    return [
        'bot_username'         => tonbankcard_api_telegram_bot_username( isset( $runtime_telegram['bot_username'] ) ? $runtime_telegram['bot_username'] : '' ),
        'webhook_secret'       => isset( $bot['webhook_secret'] ) ? (string) $bot['webhook_secret'] : ( isset( $runtime_telegram['webhook_secret'] ) ? (string) $runtime_telegram['webhook_secret'] : '' ),
        'inline_cache_seconds' => isset( $bot['inline_cache_seconds'] ) ? max( 0, (int) $bot['inline_cache_seconds'] ) : 300,
        'inline_max_results'   => isset( $bot['inline_max_results'] ) ? max( 1, min( 20, (int) $bot['inline_max_results'] ) ) : 8,
    ];
}

/**
 * Returns TRUE when the webhook secret header is acceptable.
 *
 * @param array $request
 * @param array $settings
 * @return bool
 */
function tonbankcard_api_telegram_bot_secret_allowed( array $request, array $settings ) {
    $configured = trim( isset( $settings['webhook_secret'] ) ? (string) $settings['webhook_secret'] : '' );
    if ( '' === $configured ) {
        return TRUE;
    }

    $provided = isset( $request['headers']['x-telegram-bot-api-secret-token'] )
        ? trim( (string) $request['headers']['x-telegram-bot-api-secret-token'] )
        : '';

    return '' !== $provided && hash_equals( $configured, $provided );
}

/**
 * Builds the direct Telegram Bot API method payload for a webhook update.
 *
 * @param array $update
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @param string $request_id
 * @return array
 */
function tonbankcard_api_telegram_bot_response_for_update( array $update, array $runtime, array $config, array $settings, string $request_id ) {
    if ( isset( $update['inline_query'] ) && is_array( $update['inline_query'] ) ) {
        return tonbankcard_api_telegram_bot_inline_query_response( $update['inline_query'], $runtime, $config, $settings );
    }

    if ( isset( $update['message'] ) && is_array( $update['message'] ) ) {
        return tonbankcard_api_telegram_bot_message_response( $update['message'], $runtime, $settings );
    }

    return [
        'ok'      => TRUE,
        'payload' => [],
    ];
}

/**
 * Builds a sendMessage method payload for a message command.
 *
 * @param array $message
 * @param array $runtime
 * @param array $settings
 * @return array
 */
function tonbankcard_api_telegram_bot_message_response( array $message, array $runtime, array $settings ) {
    $chat = isset( $message['chat'] ) && is_array( $message['chat'] ) ? $message['chat'] : [];
    if ( ! array_key_exists( 'id', $chat ) ) {
        return tonbankcard_api_telegram_bot_update_error( 'Telegram message updates must include chat.id.', [ 'field' => 'message.chat.id' ] );
    }

    $text = isset( $message['text'] ) ? trim( (string) $message['text'] ) : '';
    $command = tonbankcard_api_telegram_bot_parse_command( $text, $settings );
    if ( null === $command ) {
        return [
            'ok'      => TRUE,
            'payload' => [],
        ];
    }

    $language = tonbankcard_api_telegram_bot_language( isset( $message['from'] ) && is_array( $message['from'] ) ? $message['from'] : [] );
    $from_id = tonbankcard_api_telegram_bot_user_id( isset( $message['from'] ) && is_array( $message['from'] ) ? $message['from'] : [] );
    $chat_type = isset( $chat['type'] ) ? (string) $chat['type'] : 'private';
    $context = tonbankcard_api_telegram_bot_command_context( $command, $chat_type, $from_id, $runtime, $settings );

    return [
        'ok'      => TRUE,
        'payload' => [
            'method'       => 'sendMessage',
            'chat_id'      => $chat['id'],
            'text'         => tonbankcard_api_telegram_bot_command_text( $context, $language ),
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => tonbankcard_api_telegram_bot_copy( $language, $context['button_key'] ),
                            'url'  => $context['url'],
                        ],
                    ],
                ],
            ],
            'disable_web_page_preview' => TRUE,
        ],
    ];
}

/**
 * Builds an answerInlineQuery method payload.
 *
 * @param array $inline_query
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @return array
 */
function tonbankcard_api_telegram_bot_inline_query_response( array $inline_query, array $runtime, array $config, array $settings ) {
    if ( empty( $inline_query['id'] ) ) {
        return tonbankcard_api_telegram_bot_update_error( 'Telegram inline query updates must include id.', [ 'field' => 'inline_query.id' ] );
    }

    $language = tonbankcard_api_telegram_bot_language( isset( $inline_query['from'] ) && is_array( $inline_query['from'] ) ? $inline_query['from'] : [] );
    $query = isset( $inline_query['query'] ) ? trim( (string) $inline_query['query'] ) : '';
    $chat_type = isset( $inline_query['chat_type'] ) ? (string) $inline_query['chat_type'] : 'sender';
    $from_id = tonbankcard_api_telegram_bot_user_id( isset( $inline_query['from'] ) && is_array( $inline_query['from'] ) ? $inline_query['from'] : [] );
    $is_group = tonbankcard_api_telegram_bot_group_chat_type( $chat_type );
    $limit = max( 1, (int) $settings['inline_max_results'] );

    $results = tonbankcard_api_telegram_bot_market_inline_results( $query, $language, $runtime, $settings, $is_group, $from_id );
    $remaining = max( 0, $limit - count( $results ) );
    if ( $remaining > 0 ) {
        $results = array_merge(
            $results,
            tonbankcard_api_telegram_bot_coin_inline_results( $query, $language, $runtime, $config, $settings, $is_group, $from_id, $remaining )
        );
    }

    return [
        'ok'      => TRUE,
        'payload' => [
            'method'          => 'answerInlineQuery',
            'inline_query_id' => (string) $inline_query['id'],
            'results'         => array_slice( $results, 0, $limit ),
            'cache_time'      => (int) $settings['inline_cache_seconds'],
            'is_personal'     => FALSE,
            'button'          => [
                'text'    => tonbankcard_api_telegram_bot_copy( $language, 'inline_button' ),
                'web_app' => [
                    'url' => tonbankcard_api_telegram_bot_web_app_url( '/', $runtime ),
                ],
            ],
        ],
    ];
}

/**
 * Parses a message text command.
 *
 * @param string $text
 * @param array $settings
 * @return array|null
 */
function tonbankcard_api_telegram_bot_parse_command( string $text, array $settings ) {
    if ( '' === $text || '/' !== substr( $text, 0, 1 ) ) {
        return null;
    }

    $parts = preg_split( '/\s+/', $text, 2 );
    $head = isset( $parts[0] ) ? (string) $parts[0] : '';
    if ( ! preg_match( '/^\/([A-Za-z0-9_]{1,32})(?:@([A-Za-z0-9_]{5,32}))?$/', $head, $matches ) ) {
        return null;
    }

    $mentioned = isset( $matches[2] ) ? strtolower( $matches[2] ) : '';
    $bot_username = strtolower( isset( $settings['bot_username'] ) ? (string) $settings['bot_username'] : '' );
    if ( '' !== $mentioned && '' !== $bot_username && $mentioned !== $bot_username ) {
        return null;
    }

    $name = strtolower( $matches[1] );
    $aliases = [
        'help' => 'support',
    ];
    if ( isset( $aliases[ $name ] ) ) {
        $name = $aliases[ $name ];
    }

    if ( ! in_array( $name, [ 'start', 'market', 'watchlist', 'alerts', 'settings', 'support' ], TRUE ) ) {
        $name = 'support';
    }

    return [
        'name' => $name,
        'args' => isset( $parts[1] ) ? trim( (string) $parts[1] ) : '',
    ];
}

/**
 * Builds command route and link context.
 *
 * @param array $command
 * @param string $chat_type
 * @param string|null $from_id
 * @param array $runtime
 * @param array $settings
 * @return array
 */
function tonbankcard_api_telegram_bot_command_context( array $command, string $chat_type, $from_id, array $runtime, array $settings ) {
    $name = isset( $command['name'] ) ? (string) $command['name'] : 'support';
    $is_group = tonbankcard_api_telegram_bot_group_chat_type( $chat_type );
    $defaults = tonbankcard_api_telegram_bot_command_routes();
    $route = isset( $defaults[ $name ]['route'] ) ? $defaults[ $name ]['route'] : '/support';
    $context = isset( $defaults[ $name ]['context'] ) ? $defaults[ $name ]['context'] : 'bot_command';
    $campaign = 'start' === $name ? 'bot-start' : 'bot-command';
    $button_key = isset( $defaults[ $name ]['button_key'] ) ? $defaults[ $name ]['button_key'] : 'open_mini_app';
    $text_key = isset( $defaults[ $name ]['text_key'] ) ? $defaults[ $name ]['text_key'] : 'support';
    $start_param = null;

    if ( 'start' === $name ) {
        $parsed_start = tonbankcard_api_telegram_bot_start_param_from_args( isset( $command['args'] ) ? (string) $command['args'] : '' );
        if ( null !== $parsed_start ) {
            $start_param = $parsed_start['start_param'];
            $route = $parsed_start['payload']['route'];
            $campaign = $parsed_start['payload']['campaign'];
            $context = $parsed_start['payload']['context'];
            $text_key = 'start_referral';
            $button_key = 'open_shared_view';
        } elseif ( $is_group ) {
            $route = '/markets?context=group';
            $context = 'telegram_group';
            $text_key = 'group_market';
        }
    } elseif ( $is_group && in_array( $name, [ 'market', 'watchlist', 'alerts' ], TRUE ) ) {
        $route = tonbankcard_api_telegram_bot_group_route( $route );
        $context = 'telegram_group';
        $text_key = 'group_' . $name;
    }

    $url = tonbankcard_api_telegram_bot_mini_app_url(
        $route,
        $runtime,
        $settings,
        [
            'campaign' => $campaign,
            'context'  => $context,
            'inviter'  => null === $from_id ? null : 'telegram:' . $from_id,
        ],
        $start_param
    );

    return [
        'name'       => $name,
        'route'      => $route,
        'campaign'   => $campaign,
        'context'    => $context,
        'url'        => $url,
        'text_key'   => $text_key,
        'button_key' => $button_key,
    ];
}

/**
 * Returns route metadata for bot commands.
 *
 * @return array
 */
function tonbankcard_api_telegram_bot_command_routes() {
    return [
        'start'     => [
            'route'      => '/',
            'context'    => 'bot_start',
            'text_key'   => 'start',
            'button_key' => 'open_mini_app',
        ],
        'market'    => [
            'route'      => '/markets',
            'context'    => 'market_pulse',
            'text_key'   => 'market',
            'button_key' => 'open_market',
        ],
        'watchlist' => [
            'route'      => '/watchlist',
            'context'    => 'watchlist_snapshot',
            'text_key'   => 'watchlist',
            'button_key' => 'open_watchlist',
        ],
        'alerts'    => [
            'route'      => '/alerts',
            'context'    => 'alert_win',
            'text_key'   => 'alerts',
            'button_key' => 'open_alerts',
        ],
        'settings'  => [
            'route'      => '/settings',
            'context'    => 'settings',
            'text_key'   => 'settings',
            'button_key' => 'open_settings',
        ],
        'support'   => [
            'route'      => '/support',
            'context'    => 'support',
            'text_key'   => 'support',
            'button_key' => 'open_support',
        ],
    ];
}

/**
 * Parses and validates a /start argument as a share startapp payload.
 *
 * @param string $args
 * @return array|null
 */
function tonbankcard_api_telegram_bot_start_param_from_args( string $args ) {
    $candidate = trim( $args );
    if ( '' === $candidate || 0 !== strpos( $candidate, 's_' ) || ! function_exists( 'tonbankcard_api_share_parse_start_param' ) ) {
        return null;
    }

    $parsed = tonbankcard_api_share_parse_start_param( $candidate );
    if ( empty( $parsed['ok'] ) || empty( $parsed['payload'] ) || ! is_array( $parsed['payload'] ) ) {
        return null;
    }

    return [
        'start_param' => $candidate,
        'payload'     => $parsed['payload'],
    ];
}

/**
 * Returns inline market cards.
 *
 * @param string $query
 * @param string $language
 * @param array $runtime
 * @param array $settings
 * @param bool $is_group
 * @param string|null $from_id
 * @return array
 */
function tonbankcard_api_telegram_bot_market_inline_results( string $query, string $language, array $runtime, array $settings, bool $is_group, $from_id ) {
    $normalized = function_exists( 'tonbankcard_api_search_normalize_text' )
        ? tonbankcard_api_search_normalize_text( $query )
        : strtolower( trim( $query ) );

    $cards = [
        [
            'id'          => 'market_overview',
            'title'       => tonbankcard_api_telegram_bot_copy( $language, 'inline_market_title' ),
            'description' => tonbankcard_api_telegram_bot_copy( $language, 'inline_market_description' ),
            'route'       => $is_group ? '/markets?context=group' : '/markets',
            'campaign'    => 'inline-market',
            'context'     => $is_group ? 'telegram_group' : 'market_pulse',
            'keywords'    => [ 'market', 'markets', 'crypto', 'prices', 'pulse', 'overview', 'ton' ],
        ],
        [
            'id'          => 'ton_ecosystem',
            'title'       => tonbankcard_api_telegram_bot_copy( $language, 'inline_ton_title' ),
            'description' => tonbankcard_api_telegram_bot_copy( $language, 'inline_ton_description' ),
            'route'       => $is_group ? '/ton?context=group' : '/ton',
            'campaign'    => 'inline-ton',
            'context'     => $is_group ? 'telegram_group' : 'ton_movers',
            'keywords'    => [ 'ton', 'toncoin', 'ecosystem', 'telegram', 'jetton' ],
        ],
    ];

    $results = [];
    foreach ( $cards as $card ) {
        if ( '' !== $normalized && ! tonbankcard_api_telegram_bot_inline_card_matches_query( $card, $normalized ) ) {
            continue;
        }
        $results[] = tonbankcard_api_telegram_bot_inline_article_result(
            $card['id'],
            $card['title'],
            $card['description'],
            $card['route'],
            $card['campaign'],
            $card['context'],
            $language,
            $runtime,
            $settings,
            $from_id
        );
    }

    if ( empty( $results ) && '' !== $normalized ) {
        $market = $cards[0];
        $results[] = tonbankcard_api_telegram_bot_inline_article_result(
            $market['id'],
            $market['title'],
            $market['description'],
            $market['route'],
            $market['campaign'],
            $market['context'],
            $language,
            $runtime,
            $settings,
            $from_id
        );
    }

    return $results;
}

/**
 * Returns inline coin cards from local curated search entries.
 *
 * @param string $query
 * @param string $language
 * @param array $runtime
 * @param array $config
 * @param array $settings
 * @param bool $is_group
 * @param string|null $from_id
 * @param int $limit
 * @return array
 */
function tonbankcard_api_telegram_bot_coin_inline_results( string $query, string $language, array $runtime, array $config, array $settings, bool $is_group, $from_id, int $limit ) {
    if ( ! function_exists( 'tonbankcard_api_search_settings' ) || ! function_exists( 'tonbankcard_api_search_results' ) ) {
        return [];
    }

    $search_settings = tonbankcard_api_search_settings( $config );
    $entries = [];
    foreach ( isset( $search_settings['curated_entries'] ) && is_array( $search_settings['curated_entries'] ) ? $search_settings['curated_entries'] : [] as $entry ) {
        if ( function_exists( 'tonbankcard_api_search_put_entry' ) ) {
            tonbankcard_api_search_put_entry( $entries, $entry );
        } else {
            $entries[] = $entry;
        }
    }

    if ( function_exists( 'tonbankcard_api_ton_search_entries' ) ) {
        foreach ( tonbankcard_api_ton_search_entries( $runtime, $config ) as $entry ) {
            tonbankcard_api_search_put_entry( $entries, $entry );
        }
    }

    $results = tonbankcard_api_search_results( array_values( $entries ), $query, max( 1, $limit * 2 ), 'telegram_inline' );
    $inline = [];
    foreach ( $results as $result ) {
        $type = isset( $result['type'] ) ? (string) $result['type'] : '';
        if ( ! in_array( $type, [ 'coin', 'ton_asset' ], TRUE ) ) {
            continue;
        }

        $route = isset( $result['route']['path'] ) ? (string) $result['route']['path'] : '/markets';
        $title = isset( $result['title'] ) ? (string) $result['title'] : ( isset( $result['name'] ) ? (string) $result['name'] : 'Coin' );
        $symbol = isset( $result['symbol'] ) ? trim( (string) $result['symbol'] ) : '';
        $display_title = '' === $symbol ? $title : $title . ' (' . strtoupper( $symbol ) . ')';
        $description = isset( $result['subtitle'] ) && '' !== trim( (string) $result['subtitle'] )
            ? (string) $result['subtitle']
            : tonbankcard_api_telegram_bot_copy( $language, 'inline_coin_description' );

        $inline[] = tonbankcard_api_telegram_bot_inline_article_result(
            'coin_' . substr( hash( 'sha256', $type . ':' . $result['id'] ), 0, 24 ),
            $display_title,
            $description,
            $route,
            'inline-coin',
            $is_group ? 'telegram_group' : 'coin_price',
            $language,
            $runtime,
            $settings,
            $from_id,
            isset( $result['image'] ) ? (string) $result['image'] : ''
        );

        if ( count( $inline ) >= $limit ) {
            break;
        }
    }

    return $inline;
}

/**
 * Builds one Telegram inline article result.
 *
 * @param string $id
 * @param string $title
 * @param string $description
 * @param string $route
 * @param string $campaign
 * @param string $context
 * @param string $language
 * @param array $runtime
 * @param array $settings
 * @param string|null $from_id
 * @param string $thumbnail_url
 * @return array
 */
function tonbankcard_api_telegram_bot_inline_article_result( string $id, string $title, string $description, string $route, string $campaign, string $context, string $language, array $runtime, array $settings, $from_id, string $thumbnail_url = '' ) {
    $url = tonbankcard_api_telegram_bot_mini_app_url(
        $route,
        $runtime,
        $settings,
        [
            'campaign' => $campaign,
            'context'  => $context,
            'inviter'  => null === $from_id ? null : 'telegram:' . $from_id,
        ]
    );

    $result = [
        'type'                  => 'article',
        'id'                    => substr( preg_replace( '/[^A-Za-z0-9_-]/', '_', $id ), 0, 64 ),
        'title'                 => $title,
        'description'           => $description,
        'input_message_content' => [
            'message_text' => $title . "\n" . $description . "\n" . tonbankcard_api_telegram_bot_copy( $language, 'open_line' ) . ' ' . $url . "\n" . tonbankcard_api_telegram_bot_copy( $language, 'nfa' ),
        ],
        'reply_markup'          => [
            'inline_keyboard' => [
                [
                    [
                        'text' => tonbankcard_api_telegram_bot_copy( $language, 'open_mini_app' ),
                        'url'  => $url,
                    ],
                ],
            ],
        ],
    ];

    if ( 0 === strpos( $thumbnail_url, 'https://' ) ) {
        $result['thumbnail_url'] = $thumbnail_url;
    }

    return $result;
}

/**
 * Returns TRUE when a market card should be included for a normalized query.
 *
 * @param array $card
 * @param string $normalized_query
 * @return bool
 */
function tonbankcard_api_telegram_bot_inline_card_matches_query( array $card, string $normalized_query ) {
    $haystack = strtolower( $card['title'] . ' ' . $card['description'] . ' ' . implode( ' ', $card['keywords'] ) );
    return FALSE !== strpos( $haystack, $normalized_query );
}

/**
 * Builds a Mini App URL or bot startapp URL.
 *
 * @param string $route
 * @param array $runtime
 * @param array $settings
 * @param array $payload
 * @param string|null $start_param
 * @return string
 */
function tonbankcard_api_telegram_bot_mini_app_url( string $route, array $runtime, array $settings, array $payload = [], $start_param = null ) {
    if ( null === $start_param ) {
        $start_param = function_exists( 'tonbankcard_api_share_build_start_param' )
            ? tonbankcard_api_share_build_start_param(
                [
                    'route'    => $route,
                    'campaign' => isset( $payload['campaign'] ) ? $payload['campaign'] : 'bot-command',
                    'inviter'  => isset( $payload['inviter'] ) ? $payload['inviter'] : null,
                    'context'  => isset( $payload['context'] ) ? $payload['context'] : 'bot_command',
                ]
            )
            : '';
    }

    $bot_username = isset( $settings['bot_username'] ) ? (string) $settings['bot_username'] : '';
    if ( '' !== $bot_username && '' !== $start_param ) {
        return 'https://t.me/' . rawurlencode( $bot_username ) . '?startapp=' . rawurlencode( $start_param );
    }

    $url = tonbankcard_api_telegram_bot_web_app_url( $route, $runtime );
    if ( '' !== $start_param ) {
        $url .= ( FALSE === strpos( $url, '?' ) ? '?' : '&' ) . 'startapp=' . rawurlencode( $start_param );
    }

    return $url;
}

/**
 * Builds an absolute app URL for web_app buttons or fallback links.
 *
 * @param string $route
 * @param array $runtime
 * @return string
 */
function tonbankcard_api_telegram_bot_web_app_url( string $route, array $runtime ) {
    $urls = isset( $runtime['urls'] ) && is_array( $runtime['urls'] ) ? $runtime['urls'] : [];
    $base = '';
    foreach ( [ 'telegram', 'active', 'public', 'local' ] as $key ) {
        if ( ! empty( $urls[ $key ] ) ) {
            $base = (string) $urls[ $key ];
            break;
        }
    }

    if ( '' === $base ) {
        $base = '/';
    }

    return rtrim( $base, '/' ) . '/' . ltrim( $route, '/' );
}

/**
 * Returns a group-scoped version of a route.
 *
 * @param string $route
 * @return string
 */
function tonbankcard_api_telegram_bot_group_route( string $route ) {
    return $route . ( FALSE === strpos( $route, '?' ) ? '?context=group' : '&context=group' );
}

/**
 * Returns TRUE for Telegram group-like chat types.
 *
 * @param string $chat_type
 * @return bool
 */
function tonbankcard_api_telegram_bot_group_chat_type( string $chat_type ) {
    return in_array( strtolower( $chat_type ), [ 'group', 'supergroup', 'channel' ], TRUE );
}

/**
 * Extracts a safe Telegram user id from update user data.
 *
 * @param array $user
 * @return string|null
 */
function tonbankcard_api_telegram_bot_user_id( array $user ) {
    $id = isset( $user['id'] ) ? (string) $user['id'] : '';
    return preg_match( '/^[1-9][0-9]{0,19}$/', $id ) ? $id : null;
}

/**
 * Returns normalized two-letter language bucket.
 *
 * @param array $user
 * @return string
 */
function tonbankcard_api_telegram_bot_language( array $user ) {
    $language = isset( $user['language_code'] ) ? strtolower( (string) $user['language_code'] ) : 'en';
    return 0 === strpos( $language, 'ru' ) ? 'ru' : 'en';
}

/**
 * Returns a sanitized bot username without @.
 *
 * @param mixed $username
 * @return string
 */
function tonbankcard_api_telegram_bot_username( $username ) {
    $username = trim( (string) $username );
    $username = ltrim( $username, '@' );
    return preg_match( '/^[A-Za-z0-9_]{5,32}$/', $username ) ? $username : '';
}

/**
 * Returns localized message text for a command response.
 *
 * @param array $context
 * @param string $language
 * @return string
 */
function tonbankcard_api_telegram_bot_command_text( array $context, string $language ) {
    $text_key = isset( $context['text_key'] ) ? (string) $context['text_key'] : 'support';
    return tonbankcard_api_telegram_bot_copy( $language, $text_key );
}

/**
 * Returns localized bot copy.
 *
 * @param string $language
 * @param string $key
 * @return string
 */
function tonbankcard_api_telegram_bot_copy( string $language, string $key ) {
    $copy = [
        'en' => [
            'start'                     => 'TONBANKCARD is ready. Open the Mini App for markets, watchlists, alerts, and TON context.',
            'start_referral'            => 'Shared market context is ready. Open the Mini App to continue.',
            'market'                    => 'Open live market overview in the TONBANKCARD Mini App.',
            'watchlist'                 => 'Open your watchlist in the TONBANKCARD Mini App.',
            'alerts'                    => 'Open smart alerts in the TONBANKCARD Mini App.',
            'settings'                  => 'Open Mini App settings and wallet context.',
            'support'                   => 'Open support, official channels, and safety information.',
            'group_market'              => 'Open group market context without mixing it with personal data.',
            'group_watchlist'           => 'Open the group watchlist context without mixing it with personal data.',
            'group_alerts'              => 'Open group alert context without mixing it with personal data.',
            'open_mini_app'             => 'Open Mini App',
            'open_shared_view'          => 'Open shared view',
            'open_market'               => 'Open market',
            'open_watchlist'            => 'Open watchlist',
            'open_alerts'               => 'Open alerts',
            'open_settings'             => 'Open settings',
            'open_support'              => 'Open support',
            'inline_button'             => 'Open TONBANKCARD',
            'inline_market_title'       => 'Market overview',
            'inline_market_description' => 'Live crypto prices, market pulse, movers, and shareable market context.',
            'inline_ton_title'          => 'TON ecosystem',
            'inline_ton_description'    => 'Toncoin, TON assets, jettons, DeFi, and Telegram-native market context.',
            'inline_coin_description'   => 'Open price, chart, market data, and shareable context.',
            'open_line'                 => 'Open:',
            'nfa'                       => 'Not financial advice.',
        ],
        'ru' => [
            'start'                     => 'TONBANKCARD готов. Откройте Mini App для рынков, списков, алертов и контекста TON.',
            'start_referral'            => 'Общий рыночный контекст готов. Откройте Mini App, чтобы продолжить.',
            'market'                    => 'Откройте обзор рынка в Mini App TONBANKCARD.',
            'watchlist'                 => 'Откройте свой список наблюдения в Mini App TONBANKCARD.',
            'alerts'                    => 'Откройте смарт-алерты в Mini App TONBANKCARD.',
            'settings'                  => 'Откройте настройки Mini App и контекст кошелька.',
            'support'                   => 'Откройте поддержку, официальные каналы и информацию по безопасности.',
            'group_market'              => 'Откройте групповой рыночный контекст без смешивания с личными данными.',
            'group_watchlist'           => 'Откройте групповой список без смешивания с личными данными.',
            'group_alerts'              => 'Откройте групповой контекст алертов без смешивания с личными данными.',
            'open_mini_app'             => 'Открыть Mini App',
            'open_shared_view'          => 'Открыть общий вид',
            'open_market'               => 'Открыть рынок',
            'open_watchlist'            => 'Открыть список',
            'open_alerts'               => 'Открыть алерты',
            'open_settings'             => 'Открыть настройки',
            'open_support'              => 'Открыть поддержку',
            'inline_button'             => 'Открыть TONBANKCARD',
            'inline_market_title'       => 'Обзор рынка',
            'inline_market_description' => 'Цены криптовалют, рыночный пульс, лидеры движения и общий контекст.',
            'inline_ton_title'          => 'Экосистема TON',
            'inline_ton_description'    => 'Toncoin, активы TON, jetton, DeFi и контекст рынка в Telegram.',
            'inline_coin_description'   => 'Откройте цену, график, рыночные данные и общий контекст.',
            'open_line'                 => 'Открыть:',
            'nfa'                       => 'Не является финансовым советом.',
        ],
    ];

    $bucket = isset( $copy[ $language ] ) ? $language : 'en';
    return isset( $copy[ $bucket ][ $key ] ) ? $copy[ $bucket ][ $key ] : ( isset( $copy['en'][ $key ] ) ? $copy['en'][ $key ] : $key );
}

/**
 * Returns command lists for BotFather/API setup.
 *
 * @return array
 */
function tonbankcard_api_telegram_bot_commands_payload() {
    $commands = [
        'en' => [
            [ 'command' => 'start', 'description' => 'Open TONBANKCARD Mini App' ],
            [ 'command' => 'market', 'description' => 'Open market overview' ],
            [ 'command' => 'watchlist', 'description' => 'Open your watchlist' ],
            [ 'command' => 'alerts', 'description' => 'Open smart alerts' ],
            [ 'command' => 'settings', 'description' => 'Open Mini App settings' ],
            [ 'command' => 'support', 'description' => 'Support and official channels' ],
        ],
        'ru' => [
            [ 'command' => 'start', 'description' => 'Открыть Mini App TONBANKCARD' ],
            [ 'command' => 'market', 'description' => 'Открыть обзор рынка' ],
            [ 'command' => 'watchlist', 'description' => 'Открыть список наблюдения' ],
            [ 'command' => 'alerts', 'description' => 'Открыть смарт-алерты' ],
            [ 'command' => 'settings', 'description' => 'Открыть настройки Mini App' ],
            [ 'command' => 'support', 'description' => 'Поддержка и официальные каналы' ],
        ],
    ];

    return [
        'commands'          => $commands,
        'telegram_payloads' => [
            [
                'method'   => 'setMyCommands',
                'commands' => $commands['en'],
            ],
            [
                'method'        => 'setMyCommands',
                'commands'      => $commands['ru'],
                'language_code' => 'ru',
            ],
        ],
    ];
}

/**
 * Builds a typed update error result.
 *
 * @param string $message
 * @param array $details
 * @return array
 */
function tonbankcard_api_telegram_bot_update_error( string $message, array $details ) {
    return [
        'ok'      => FALSE,
        'status'  => 400,
        'code'    => 'telegram_bot_invalid_update',
        'message' => $message,
        'details' => $details,
    ];
}

/**
 * Logs and returns a normal API-envelope error response for webhook failures.
 *
 * @param int $status
 * @param string $code
 * @param string $message
 * @param array $details
 * @param string $request_id
 * @param array $runtime
 * @param array $config
 * @param array $headers
 * @return array
 */
function tonbankcard_api_telegram_bot_error_response( int $status, string $code, string $message, array $details, string $request_id, array $runtime, array $config, array $headers ) {
    tonbankcard_observability_log(
        $runtime,
        $config,
        'warning',
        'telegram_bot.webhook_error',
        [
            'request_id' => $request_id,
            'error_code' => $code,
            'status'     => $status,
        ]
    );

    return tonbankcard_api_error_response(
        $status,
        $code,
        $message,
        $details,
        $request_id,
        $headers
    );
}

/**
 * Returns a direct JSON response for Telegram webhook success.
 *
 * @param array $payload
 * @param array $headers
 * @return array
 */
function tonbankcard_api_telegram_bot_json_response( array $payload, array $headers ) {
    $body = json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE );
    if ( FALSE === $body ) {
        $body = '{}';
    }

    return [
        'status'  => 200,
        'headers' => $headers,
        'body'    => $body,
    ];
}
