<?php
/**
 * -------------------------------------------------------------------------
 * TONBANKCARD V2 AI PROVIDER FOUNDATION
 * -------------------------------------------------------------------------
 * Server-owned AI insight execution with Groq as the first configurable
 * provider. Provider credentials and raw prompts stay server-side.
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * Returns TRUE when a normalized path belongs to the AI API surface.
 *
 * @param string $path
 * @return bool
 */
function tonbankcard_api_ai_is_request( string $path ) {
    return '/api/ai' === $path || 0 === strpos( $path, '/api/ai/' );
}

/**
 * Returns documented AI provider routes.
 *
 * @return array
 */
function tonbankcard_api_ai_routes() {
    return [
        '/api/ai',
        '/api/ai/insight',
    ];
}

/**
 * Handles an AI API request using the shared API envelope helpers.
 *
 * @param array $request
 * @param array $runtime
 * @param array $config
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_ai_handle( array $request, array $runtime, array $config, string $request_id, array $headers ) {
    $path = tonbankcard_api_normalize_path( $request['path'] );
    $provider = tonbankcard_api_ai_provider_config( $runtime, $config );

    if ( '/api/ai' === $path ) {
        if ( 'GET' !== $request['method'] ) {
            return tonbankcard_api_method_not_allowed_response( [ 'GET', 'OPTIONS' ], $request_id, $headers );
        }

        return tonbankcard_api_success_response(
            [
                'service'  => 'tonbankcard-ai-provider',
                'provider' => tonbankcard_api_ai_provider_meta( $provider ),
                'routes'   => tonbankcard_api_ai_routes(),
            ],
            $request_id,
            $headers
        );
    }

    if ( '/api/ai/insight' !== $path ) {
        return tonbankcard_api_error_response(
            404,
            'not_found',
            'No AI route matches the request.',
            [ 'path' => $path ],
            $request_id,
            $headers
        );
    }

    if ( 'POST' !== $request['method'] ) {
        return tonbankcard_api_method_not_allowed_response( [ 'POST', 'OPTIONS' ], $request_id, $headers );
    }

    $payload = tonbankcard_api_json_body( $request );
    $validation = tonbankcard_api_ai_validate_request_payload( $payload );
    if ( empty( $validation['ok'] ) ) {
        return tonbankcard_api_error_response(
            400,
            isset( $validation['code'] ) ? $validation['code'] : 'invalid_ai_request',
            isset( $validation['message'] ) ? $validation['message'] : 'The AI insight request is invalid.',
            isset( $validation['details'] ) && is_array( $validation['details'] ) ? $validation['details'] : [],
            $request_id,
            $headers
        );
    }

    return tonbankcard_api_ai_insight_response( $validation['context'], $runtime, $config, $provider, $request_id, $headers );
}

/**
 * Builds safe provider configuration from runtime and API config.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_ai_provider_config( array $runtime, array $config ) {
    $ai = isset( $config['ai'] ) && is_array( $config['ai'] ) ? $config['ai'] : [];
    $runtime_ai = isset( $runtime['ai'] ) && is_array( $runtime['ai'] ) ? $runtime['ai'] : [];
    $features = isset( $runtime['feature_flags'] ) && is_array( $runtime['feature_flags'] ) ? $runtime['feature_flags'] : [];

    $provider_name = isset( $ai['provider'] ) ? strtolower( (string) $ai['provider'] ) : ( isset( $runtime_ai['provider'] ) ? strtolower( (string) $runtime_ai['provider'] ) : 'groq' );
    if ( ! in_array( $provider_name, [ 'groq' ], TRUE ) ) {
        $provider_name = 'groq';
    }

    $runtime_provider = isset( $runtime['providers'][ $provider_name ] ) && is_array( $runtime['providers'][ $provider_name ] )
        ? $runtime['providers'][ $provider_name ]
        : [];
    $configured_provider = isset( $ai[ $provider_name ] ) && is_array( $ai[ $provider_name ] ) ? $ai[ $provider_name ] : [];

    $api_key = isset( $configured_provider['api_key'] ) ? (string) $configured_provider['api_key'] : ( isset( $runtime_provider['api_key'] ) ? (string) $runtime_provider['api_key'] : '' );
    $base_url = isset( $configured_provider['base_url'] ) ? (string) $configured_provider['base_url'] : ( isset( $runtime_provider['base_url'] ) ? (string) $runtime_provider['base_url'] : 'https://api.groq.com/openai/v1/' );
    $base_url = rtrim( trim( $base_url ), '/' ) . '/';

    $rate_limit = isset( $configured_provider['rate_limit'] ) && is_array( $configured_provider['rate_limit'] )
        ? $configured_provider['rate_limit']
        : ( isset( $runtime_provider['rate_limit'] ) && is_array( $runtime_provider['rate_limit'] ) ? $runtime_provider['rate_limit'] : [] );

    $enabled_features = isset( $ai['enabled_features'] ) && is_array( $ai['enabled_features'] )
        ? $ai['enabled_features']
        : ( isset( $runtime_ai['enabled_features'] ) && is_array( $runtime_ai['enabled_features'] ) ? $runtime_ai['enabled_features'] : [ 'summary', 'sentiment', 'insight' ] );

    return [
        'name'               => $provider_name,
        'api_key'            => $api_key,
        'api_key_configured' => '' !== trim( $api_key ),
        'base_url'           => $base_url,
        'endpoint'           => $base_url . 'chat/completions',
        'model_id'           => isset( $configured_provider['model_id'] ) ? (string) $configured_provider['model_id'] : ( isset( $runtime_provider['model_id'] ) ? (string) $runtime_provider['model_id'] : 'llama-3.3-70b-versatile' ),
        'timeout_seconds'    => isset( $configured_provider['timeout_seconds'] ) ? max( 1, (int) $configured_provider['timeout_seconds'] ) : ( isset( $runtime_provider['timeout_seconds'] ) ? max( 1, (int) $runtime_provider['timeout_seconds'] ) : 10 ),
        'rate_limit'         => [
            'window_seconds' => isset( $rate_limit['window_seconds'] ) ? max( 1, (int) $rate_limit['window_seconds'] ) : 60,
            'max_requests'   => isset( $rate_limit['max_requests'] ) ? max( 1, (int) $rate_limit['max_requests'] ) : 20,
        ],
        'prompt_version'     => isset( $ai['prompt_version'] ) ? (string) $ai['prompt_version'] : ( isset( $runtime_ai['prompt_version'] ) ? (string) $runtime_ai['prompt_version'] : 'v1' ),
        'enabled_features'   => array_values( array_intersect( [ 'summary', 'sentiment', 'insight' ], $enabled_features ) ),
        'fallback_behavior'  => isset( $ai['fallback_behavior'] ) ? (string) $ai['fallback_behavior'] : ( isset( $runtime_ai['fallback_behavior'] ) ? (string) $runtime_ai['fallback_behavior'] : 'unavailable' ),
        'feature_enabled'    => ! empty( $features['ai'] ),
    ];
}

/**
 * Returns safe AI provider metadata.
 *
 * @param array $provider
 * @return array
 */
function tonbankcard_api_ai_provider_meta( array $provider ) {
    return [
        'name'              => $provider['name'],
        'model_id'          => $provider['model_id'],
        'prompt_version'    => $provider['prompt_version'],
        'enabled_features'  => $provider['enabled_features'],
        'fallback_behavior' => $provider['fallback_behavior'],
        'credentialed'      => (bool) $provider['api_key_configured'],
        'rate_limit'        => $provider['rate_limit'],
    ];
}

/**
 * Returns AI provider health metadata without exposing keys or prompts.
 *
 * @param array $runtime
 * @param array $config
 * @return array
 */
function tonbankcard_api_ai_provider_health_check( array $runtime, array $config ) {
    $provider = tonbankcard_api_ai_provider_config( $runtime, $config );
    $required = ! empty( $provider['feature_enabled'] );
    $configured = ! empty( $provider['api_key_configured'] );

    return [
        'status'        => $configured ? 'configured' : ( $required ? 'fail' : 'not_configured' ),
        'required'      => $required,
        'configured'    => $configured,
        'available'     => null,
        'model_id'      => $provider['model_id'],
        'prompt_version' => $provider['prompt_version'],
        'enabled_features' => $provider['enabled_features'],
        'fallback_behavior' => $provider['fallback_behavior'],
        'rate_limit'    => $provider['rate_limit'],
        'cost_counters' => [
            'tracked' => [ 'requests', 'input_tokens', 'output_tokens', 'total_tokens', 'provider_failures' ],
            'estimated_cost_usd' => null,
        ],
        'message'       => $configured ? 'Groq is configured server-side for AI insight generation.' : ( $required ? 'Groq API key is required when AI features are enabled.' : 'Groq is optional while AI features are disabled.' ),
    ];
}

/**
 * Validates and normalizes a client AI insight request.
 *
 * @param array $payload
 * @return array
 */
function tonbankcard_api_ai_validate_request_payload( array $payload ) {
    $type = isset( $payload['insight_type'] ) ? strtolower( trim( (string) $payload['insight_type'] ) ) : '';
    $allowed_types = [ 'market_summary', 'coin_summary', 'watchlist_digest', 'alert_explanation', 'sentiment' ];
    if ( ! in_array( $type, $allowed_types, TRUE ) ) {
        return [
            'ok'      => FALSE,
            'code'    => 'unsupported_insight_type',
            'message' => 'AI insight_type is not supported.',
            'details' => [ 'allowed' => $allowed_types ],
        ];
    }

    $subject = isset( $payload['subject'] ) ? tonbankcard_api_ai_clean_text( (string) $payload['subject'], 160 ) : '';
    if ( '' === $subject ) {
        return [
            'ok'      => FALSE,
            'code'    => 'missing_subject',
            'message' => 'AI insight subject is required.',
            'details' => [ 'field' => 'subject' ],
        ];
    }

    if ( ! isset( $payload['market_data_age_seconds'] ) || ! is_numeric( $payload['market_data_age_seconds'] ) ) {
        return [
            'ok'      => FALSE,
            'code'    => 'missing_market_data_age',
            'message' => 'AI insights require market_data_age_seconds.',
            'details' => [ 'field' => 'market_data_age_seconds' ],
        ];
    }

    return [
        'ok'      => TRUE,
        'context' => [
            'insight_type'            => $type,
            'feature'                 => tonbankcard_api_ai_feature_for_insight_type( $type ),
            'subject'                 => $subject,
            'market_data_age_seconds' => max( 0, (int) $payload['market_data_age_seconds'] ),
            'market_data_updated_at'  => isset( $payload['market_data_updated_at'] ) ? tonbankcard_api_ai_clean_text( (string) $payload['market_data_updated_at'], 80 ) : null,
            'market_data'             => tonbankcard_api_ai_sanitize_context( isset( $payload['market_data'] ) ? $payload['market_data'] : [] ),
        ],
    ];
}

/**
 * Maps an insight type to the configured AI feature bucket.
 *
 * @param string $type
 * @return string
 */
function tonbankcard_api_ai_feature_for_insight_type( string $type ) {
    if ( 'sentiment' === $type ) {
        return 'sentiment';
    }
    if ( in_array( $type, [ 'market_summary', 'coin_summary', 'watchlist_digest' ], TRUE ) ) {
        return 'summary';
    }

    return 'insight';
}

/**
 * Builds an insight response or a graceful unavailable fallback.
 *
 * @param array $context
 * @param array $runtime
 * @param array $config
 * @param array $provider
 * @param string $request_id
 * @param array $headers
 * @return array
 */
function tonbankcard_api_ai_insight_response( array $context, array $runtime, array $config, array $provider, string $request_id, array $headers ) {
    if ( empty( $provider['feature_enabled'] ) ) {
        return tonbankcard_api_ai_unavailable_response( 'ai_disabled', $provider, $request_id, $headers );
    }

    if ( empty( $provider['api_key_configured'] ) ) {
        return tonbankcard_api_ai_unavailable_response( 'provider_not_configured', $provider, $request_id, $headers );
    }

    if ( ! in_array( $context['feature'], $provider['enabled_features'], TRUE ) ) {
        return tonbankcard_api_ai_unavailable_response( 'feature_disabled', $provider, $request_id, $headers );
    }

    $execution = tonbankcard_api_ai_execute_prompt( $context, $provider, $config );
    if ( empty( $execution['ok'] ) ) {
        return tonbankcard_api_ai_unavailable_response(
            isset( $execution['reason'] ) ? $execution['reason'] : 'provider_unavailable',
            $provider,
            $request_id,
            $headers,
            isset( $execution['cost'] ) && is_array( $execution['cost'] ) ? $execution['cost'] : []
        );
    }

    return tonbankcard_api_success_response(
        [
            'status'         => 'available',
            'insight'        => $execution['insight'],
            'provider'       => tonbankcard_api_ai_provider_meta( $provider ),
            'prompt_version' => $provider['prompt_version'],
        ],
        $request_id,
        $headers,
        200,
        [
            'provider' => tonbankcard_api_ai_provider_meta( $provider ),
            'cost'     => $execution['cost'],
        ]
    );
}

/**
 * Builds the safe AI fallback payload.
 *
 * @param string $reason
 * @param array $provider
 * @param string $request_id
 * @param array $headers
 * @param array $cost
 * @return array
 */
function tonbankcard_api_ai_unavailable_response( string $reason, array $provider, string $request_id, array $headers, array $cost = [] ) {
    return tonbankcard_api_success_response(
        [
            'status'         => 'insight unavailable',
            'insight'        => null,
            'reason'         => $reason,
            'provider'       => tonbankcard_api_ai_provider_meta( $provider ),
            'prompt_version' => $provider['prompt_version'],
        ],
        $request_id,
        $headers,
        200,
        [
            'provider' => tonbankcard_api_ai_provider_meta( $provider ),
            'cost'     => tonbankcard_api_ai_cost_counters( [], ! empty( $cost ) ? $cost : [] ),
        ]
    );
}

/**
 * Executes an AI prompt through the configured provider transport.
 *
 * @param array $context
 * @param array $provider
 * @param array $config
 * @return array
 */
function tonbankcard_api_ai_execute_prompt( array $context, array $provider, array $config ) {
    $request = tonbankcard_api_ai_provider_request( $context, $provider );

    if ( isset( $config['ai']['transport'] ) && is_callable( $config['ai']['transport'] ) ) {
        $response = call_user_func( $config['ai']['transport'], $request );
    } elseif ( function_exists( 'curl_init' ) ) {
        $response = tonbankcard_api_ai_fetch_with_curl( $request );
    } else {
        $response = tonbankcard_api_ai_fetch_with_stream( $request );
    }

    $status = isset( $response['status'] ) ? (int) $response['status'] : 0;
    if ( ! empty( $response['error'] ) ) {
        return [
            'ok'     => FALSE,
            'reason' => 'timeout' === $response['error'] ? 'provider_timeout' : 'provider_unavailable',
            'cost'   => tonbankcard_api_ai_cost_counters( [], [ 'requests' => 1, 'provider_failures' => 1 ] ),
        ];
    }

    if ( 429 === $status ) {
        return [
            'ok'     => FALSE,
            'reason' => 'provider_rate_limited',
            'cost'   => tonbankcard_api_ai_cost_counters( [], [ 'requests' => 1, 'provider_failures' => 1 ] ),
        ];
    }

    if ( 401 === $status || 403 === $status ) {
        return [
            'ok'     => FALSE,
            'reason' => 'provider_auth_failed',
            'cost'   => tonbankcard_api_ai_cost_counters( [], [ 'requests' => 1, 'provider_failures' => 1 ] ),
        ];
    }

    if ( 408 === $status || 504 === $status ) {
        return [
            'ok'     => FALSE,
            'reason' => 'provider_timeout',
            'cost'   => tonbankcard_api_ai_cost_counters( [], [ 'requests' => 1, 'provider_failures' => 1 ] ),
        ];
    }

    if ( $status < 200 || $status >= 300 ) {
        return [
            'ok'     => FALSE,
            'reason' => 'provider_unavailable',
            'cost'   => tonbankcard_api_ai_cost_counters( [], [ 'requests' => 1, 'provider_failures' => 1 ] ),
        ];
    }

    $provider_payload = json_decode( isset( $response['body'] ) ? (string) $response['body'] : '', TRUE );
    if ( ! is_array( $provider_payload ) ) {
        return [
            'ok'     => FALSE,
            'reason' => 'provider_invalid_json',
            'cost'   => tonbankcard_api_ai_cost_counters( [], [ 'requests' => 1, 'provider_failures' => 1 ] ),
        ];
    }

    $content = tonbankcard_api_ai_provider_content( $provider_payload );
    $model_payload = json_decode( $content, TRUE );
    if ( '' === $content || ! is_array( $model_payload ) ) {
        return [
            'ok'     => FALSE,
            'reason' => 'schema_validation_failed',
            'cost'   => tonbankcard_api_ai_cost_counters( $provider_payload, [ 'requests' => 1, 'provider_failures' => 1 ] ),
        ];
    }

    $validation = tonbankcard_api_ai_validate_provider_output( $model_payload, $context );
    if ( empty( $validation['ok'] ) ) {
        return [
            'ok'     => FALSE,
            'reason' => 'schema_validation_failed',
            'cost'   => tonbankcard_api_ai_cost_counters( $provider_payload, [ 'requests' => 1, 'provider_failures' => 1 ] ),
        ];
    }

    return [
        'ok'      => TRUE,
        'insight' => $validation['insight'],
        'cost'    => tonbankcard_api_ai_cost_counters( $provider_payload, [ 'requests' => 1, 'provider_failures' => 0 ] ),
    ];
}

/**
 * Builds the provider request without exposing it to logs or clients.
 *
 * @param array $context
 * @param array $provider
 * @return array
 */
function tonbankcard_api_ai_provider_request( array $context, array $provider ) {
    $body = [
        'model'                 => $provider['model_id'],
        'messages'              => tonbankcard_api_ai_prompt_messages( $context, $provider ),
        'temperature'           => 0.2,
        'max_completion_tokens' => 650,
        'response_format'       => [
            'type' => 'json_object',
        ],
    ];

    return [
        'method'          => 'POST',
        'url'             => $provider['endpoint'],
        'headers'         => [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $provider['api_key'],
            'User-Agent'    => 'TONBANKCARD-AIProvider/' . GECKO_CLIENT_VERSION,
            'Groq-Beta'     => 'inference-metrics',
        ],
        'body'            => json_encode( $body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
        'timeout_seconds' => $provider['timeout_seconds'],
        'provider'        => $provider['name'],
        'model_id'        => $provider['model_id'],
    ];
}

/**
 * Builds structured prompt messages from trusted server-side fields.
 *
 * @param array $context
 * @param array $provider
 * @return array
 */
function tonbankcard_api_ai_prompt_messages( array $context, array $provider ) {
    return [
        [
            'role'    => 'system',
            'content' => 'You write concise factual crypto market context for TONBANKCARD. Return only structured JSON. Do not provide investment advice. Do not tell users to buy, sell, hold, short, long, use leverage, set stop losses, or size positions. Include not financial advice framing, cite market_data_age_seconds, and include uncertainty. Use neutral language when evidence is mixed.',
        ],
        [
            'role'    => 'user',
            'content' => json_encode(
                [
                    'schema' => [
                        'title'                   => 'short string',
                        'summary'                 => 'one or two factual sentences, not financial advice',
                        'sentiment'               => 'bullish, bearish, neutral, mixed, or unknown',
                        'confidence'              => 'number from 0 to 1',
                        'drivers'                 => 'array of up to five factual strings',
                        'risks'                   => 'array of up to five factual strings',
                        'uncertainty'             => 'required string explaining uncertainty',
                        'market_data_age_seconds' => 'copy this integer from the input',
                        'not_financial_advice'    => TRUE,
                    ],
                    'prompt_version' => $provider['prompt_version'],
                    'input'          => $context,
                ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
        ],
    ];
}

/**
 * Executes the provider request with curl.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_ai_fetch_with_curl( array $request ) {
    $handle = curl_init( $request['url'] );
    if ( FALSE === $handle ) {
        return [
            'status' => 0,
            'error'  => 'transport_unavailable',
        ];
    }

    $headers = [];
    foreach ( $request['headers'] as $name => $value ) {
        $headers[] = $name . ': ' . $value;
    }

    curl_setopt_array(
        $handle,
        [
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => $request['body'],
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => $request['timeout_seconds'],
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADER         => TRUE,
        ]
    );

    $raw = curl_exec( $handle );
    if ( FALSE === $raw ) {
        $errno = curl_errno( $handle );
        curl_close( $handle );
        return [
            'status' => 0,
            'error'  => 28 === $errno ? 'timeout' : 'transport_unavailable',
        ];
    }

    $status = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE );
    $header_size = (int) curl_getinfo( $handle, CURLINFO_HEADER_SIZE );
    curl_close( $handle );

    $header_text = substr( $raw, 0, $header_size );
    $body = substr( $raw, $header_size );

    return [
        'status'  => $status,
        'headers' => tonbankcard_api_ai_parse_header_lines( explode( "\n", $header_text ) ),
        'body'    => FALSE === $body ? '' : $body,
    ];
}

/**
 * Executes the provider request with PHP streams.
 *
 * @param array $request
 * @return array
 */
function tonbankcard_api_ai_fetch_with_stream( array $request ) {
    $headers = [];
    foreach ( $request['headers'] as $name => $value ) {
        $headers[] = $name . ': ' . $value;
    }

    $context = stream_context_create(
        [
            'http' => [
                'method'        => 'POST',
                'header'        => implode( "\r\n", $headers ),
                'content'       => $request['body'],
                'timeout'       => $request['timeout_seconds'],
                'ignore_errors' => TRUE,
            ],
        ]
    );

    $body = @file_get_contents( $request['url'], FALSE, $context );
    $headers = tonbankcard_api_ai_parse_header_lines( isset( $http_response_header ) ? $http_response_header : [] );
    $status = isset( $headers[':status'] ) ? (int) $headers[':status'] : 0;

    if ( FALSE === $body ) {
        return [
            'status' => $status,
            'error'  => 'transport_unavailable',
        ];
    }

    return [
        'status'  => $status,
        'headers' => $headers,
        'body'    => $body,
    ];
}

/**
 * Parses HTTP response header lines.
 *
 * @param array $lines
 * @return array
 */
function tonbankcard_api_ai_parse_header_lines( array $lines ) {
    $headers = [];
    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( '' === $line ) {
            continue;
        }
        if ( preg_match( '/^HTTP\/[0-9.]+\s+([0-9]{3})/', $line, $matches ) ) {
            $headers[':status'] = (int) $matches[1];
            continue;
        }

        $separator = strpos( $line, ':' );
        if ( FALSE === $separator ) {
            continue;
        }

        $headers[ strtolower( trim( substr( $line, 0, $separator ) ) ) ] = trim( substr( $line, $separator + 1 ) );
    }

    return $headers;
}

/**
 * Extracts model message content from an OpenAI-compatible response.
 *
 * @param array $provider_payload
 * @return string
 */
function tonbankcard_api_ai_provider_content( array $provider_payload ) {
    if ( isset( $provider_payload['choices'][0]['message']['content'] ) ) {
        return trim( (string) $provider_payload['choices'][0]['message']['content'] );
    }

    return '';
}

/**
 * Validates provider JSON before it can reach a user response.
 *
 * @param array $payload
 * @param array $context
 * @return array
 */
function tonbankcard_api_ai_validate_provider_output( array $payload, array $context ) {
    $title = isset( $payload['title'] ) ? tonbankcard_api_ai_clean_text( (string) $payload['title'], 120 ) : '';
    $summary = isset( $payload['summary'] ) ? tonbankcard_api_ai_clean_text( (string) $payload['summary'], 1200 ) : '';
    $sentiment = isset( $payload['sentiment'] ) ? strtolower( trim( (string) $payload['sentiment'] ) ) : '';
    $confidence = isset( $payload['confidence'] ) && is_numeric( $payload['confidence'] ) ? (float) $payload['confidence'] : null;
    $uncertainty = isset( $payload['uncertainty'] ) ? tonbankcard_api_ai_clean_text( (string) $payload['uncertainty'], 500 ) : '';

    if ( '' === $title || '' === $summary || '' === $uncertainty ) {
        return [ 'ok' => FALSE ];
    }
    if ( ! in_array( $sentiment, [ 'bullish', 'bearish', 'neutral', 'mixed', 'unknown' ], TRUE ) ) {
        return [ 'ok' => FALSE ];
    }
    if ( null === $confidence || $confidence < 0 || $confidence > 1 ) {
        return [ 'ok' => FALSE ];
    }
    if ( empty( $payload['not_financial_advice'] ) || TRUE !== (bool) $payload['not_financial_advice'] ) {
        return [ 'ok' => FALSE ];
    }
    if ( ! isset( $payload['market_data_age_seconds'] ) || ! is_numeric( $payload['market_data_age_seconds'] ) ) {
        return [ 'ok' => FALSE ];
    }

    $drivers = tonbankcard_api_ai_clean_string_list( isset( $payload['drivers'] ) && is_array( $payload['drivers'] ) ? $payload['drivers'] : [], 5, 220 );
    $risks = tonbankcard_api_ai_clean_string_list( isset( $payload['risks'] ) && is_array( $payload['risks'] ) ? $payload['risks'] : [], 5, 220 );

    $advice_text = implode( ' ', array_merge( [ $title, $summary, $uncertainty ], $drivers, $risks ) );
    if ( tonbankcard_api_ai_contains_advice( $advice_text ) ) {
        return [ 'ok' => FALSE ];
    }

    return [
        'ok'      => TRUE,
        'insight' => [
            'type'        => $context['insight_type'],
            'title'       => $title,
            'summary'     => $summary,
            'sentiment'   => $sentiment,
            'confidence'  => $confidence,
            'drivers'     => $drivers,
            'risks'       => $risks,
            'uncertainty' => $uncertainty,
            'safety'      => [
                'not_financial_advice' => TRUE,
            ],
            'freshness'   => [
                'market_data_age_seconds' => $context['market_data_age_seconds'],
                'market_data_updated_at'  => $context['market_data_updated_at'],
            ],
        ],
    ];
}

/**
 * Returns TRUE when generated text looks like investment advice.
 *
 * @param string $text
 * @return bool
 */
function tonbankcard_api_ai_contains_advice( string $text ) {
    return 1 === preg_match( '/\b(buy|sell|hold|short(?!-term)|long(?!-term)|leverage|take profit|stop loss|position size|all in)\b/i', $text );
}

/**
 * Sanitizes bounded text.
 *
 * @param string $text
 * @param int $max_length
 * @return string
 */
function tonbankcard_api_ai_clean_text( string $text, int $max_length ) {
    $text = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text );
    $text = preg_replace( '/\s+/', ' ', trim( (string) $text ) );
    if ( strlen( $text ) > $max_length ) {
        $text = substr( $text, 0, $max_length );
    }

    return $text;
}

/**
 * Sanitizes an array of bounded strings.
 *
 * @param array $values
 * @param int $max_items
 * @param int $max_length
 * @return array
 */
function tonbankcard_api_ai_clean_string_list( array $values, int $max_items, int $max_length ) {
    $clean = [];
    foreach ( $values as $value ) {
        $item = tonbankcard_api_ai_clean_text( (string) $value, $max_length );
        if ( '' !== $item ) {
            $clean[] = $item;
        }
        if ( count( $clean ) >= $max_items ) {
            break;
        }
    }

    return $clean;
}

/**
 * Sanitizes structured market context before prompt construction.
 *
 * @param mixed $value
 * @param int $depth
 * @return mixed
 */
function tonbankcard_api_ai_sanitize_context( $value, int $depth = 0 ) {
    if ( $depth > 3 ) {
        return null;
    }

    if ( is_array( $value ) ) {
        $clean = [];
        $count = 0;
        foreach ( $value as $key => $item ) {
            $key = preg_replace( '/[^A-Za-z0-9_.-]/', '', (string) $key );
            if ( '' === $key ) {
                continue;
            }
            $clean[ substr( $key, 0, 64 ) ] = tonbankcard_api_ai_sanitize_context( $item, $depth + 1 );
            $count++;
            if ( $count >= 40 ) {
                break;
            }
        }
        return $clean;
    }

    if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
        return $value;
    }

    return tonbankcard_api_ai_clean_text( (string) $value, 500 );
}

/**
 * Builds safe cost counters from provider usage.
 *
 * @param array $provider_payload
 * @param array $overrides
 * @return array
 */
function tonbankcard_api_ai_cost_counters( array $provider_payload = [], array $overrides = [] ) {
    $usage = isset( $provider_payload['usage'] ) && is_array( $provider_payload['usage'] ) ? $provider_payload['usage'] : [];

    if ( empty( $usage ) && isset( $provider_payload['usage_breakdown']['models'] ) && is_array( $provider_payload['usage_breakdown']['models'] ) ) {
        $usage = [
            'prompt_tokens'     => 0,
            'completion_tokens' => 0,
            'total_tokens'      => 0,
        ];
        foreach ( $provider_payload['usage_breakdown']['models'] as $model_usage ) {
            $model_usage = isset( $model_usage['usage'] ) && is_array( $model_usage['usage'] ) ? $model_usage['usage'] : [];
            $usage['prompt_tokens'] += isset( $model_usage['prompt_tokens'] ) ? (int) $model_usage['prompt_tokens'] : 0;
            $usage['completion_tokens'] += isset( $model_usage['completion_tokens'] ) ? (int) $model_usage['completion_tokens'] : 0;
            $usage['total_tokens'] += isset( $model_usage['total_tokens'] ) ? (int) $model_usage['total_tokens'] : 0;
        }
    }

    $input_tokens = isset( $usage['prompt_tokens'] ) ? (int) $usage['prompt_tokens'] : ( isset( $usage['input_tokens'] ) ? (int) $usage['input_tokens'] : 0 );
    $output_tokens = isset( $usage['completion_tokens'] ) ? (int) $usage['completion_tokens'] : ( isset( $usage['output_tokens'] ) ? (int) $usage['output_tokens'] : 0 );
    $total_tokens = isset( $usage['total_tokens'] ) ? (int) $usage['total_tokens'] : $input_tokens + $output_tokens;

    $counters = [
        'requests'           => 0,
        'provider_failures'  => 0,
        'input_tokens'       => $input_tokens,
        'output_tokens'      => $output_tokens,
        'total_tokens'       => $total_tokens,
        'estimated_cost_usd' => null,
    ];

    foreach ( $overrides as $key => $value ) {
        if ( array_key_exists( $key, $counters ) ) {
            $counters[ $key ] = $value;
        }
    }

    return $counters;
}
