<?php
/**
 * -------------------------------------------------------------------------
 * GECKO CLIENT
 * -------------------------------------------------------------------------
 * @package     Gecko Client
 * @author      RunCoders
 * @license     Envato Market Regular License (https://1.envato.market/regular-license)
 * @copyright   Copyright (c) 2021 RunCoders (https://runcoders.net)
 * @since	    1.0.0
 */

defined( 'GECKO_CLIENT_VERSION' ) OR exit( 'No direct script access allowed' );

/**
 * @since 1.0.0
 * Get the modified time of a file
 *
 * @param string $file_path
 * @return int
 */
function file_modified_time( string $file_path ) {
    if ( file_exists( $file_path ) ) {
        return filemtime( $file_path ) ?: 0;
    }
    return 0;
}

/**
 * @since 1.0.0
 * Gets the Base URL for current environment
 *
 * @return null|string
 */
function base_url() {
    if ( ! empty( $GLOBALS['runtime_config']['urls']['active'] ) ) {
        return $GLOBALS['runtime_config']['urls']['active'];
    }

    if ( ! empty( $GLOBALS['site']['active_base_url'] ) ) {
        return $GLOBALS['site']['active_base_url'];
    }

    if ( defined( 'TONBANKCARD_PROFILE' ) && ! empty( $GLOBALS['site']['base_url'][ TONBANKCARD_PROFILE ] ) ) {
        return $GLOBALS['site']['base_url'][ TONBANKCARD_PROFILE ];
    }

    return empty( $GLOBALS['site']['base_url'][ GECKO_CLIENT_ENV ] ) ? null : $GLOBALS['site']['base_url'][ GECKO_CLIENT_ENV ];
}

/**
 * @since 1.0.0
 * Generates URL appending a path to base_url
 *
 * @param string $path
 * @return string
 */
function site_url( string $path = '' ) {
    return rtrim( base_url(), '/' ) . '/' . ltrim( $path, '/' );
}

/**
 * Returns the current browser path normalized for route matching.
 *
 * @return string
 */
function tonbankcard_current_path() {
    $path = '/';
    if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
        $parsed_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
        if ( is_string( $parsed_path ) && '' !== $parsed_path ) {
            $path = $parsed_path;
        }
    }

    $base_path = parse_url( (string) base_url(), PHP_URL_PATH );
    if ( is_string( $base_path ) && '' !== $base_path && '/' !== $base_path ) {
        $base_path = '/' . trim( $base_path, '/' );
        if ( $path === $base_path ) {
            $path = '/';
        } elseif ( 0 === strpos( $path, $base_path . '/' ) ) {
            $path = substr( $path, strlen( $base_path ) );
        }
    }

    return tonbankcard_normalize_path( $path );
}

/**
 * Normalizes a route path for public route matching.
 *
 * @param string $path
 * @return string
 */
function tonbankcard_normalize_path( string $path ) {
    $path = '/' . ltrim( $path, '/' );
    if ( '/' !== $path ) {
        $path = rtrim( $path, '/' );
    }
    return $path;
}

/**
 * Returns browser security headers for the public shell or JSON API.
 *
 * @param string $context html|api
 * @return array
 */
function tonbankcard_security_headers( string $context = 'html' ) {
    $headers = [
        'X-Content-Type-Options'  => 'nosniff',
        'Referrer-Policy'         => 'strict-origin-when-cross-origin',
        'Permissions-Policy'      => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()',
        'Cross-Origin-Opener-Policy' => 'same-origin-allow-popups',
    ];

    if ( 'html' === $context ) {
        $headers['Content-Security-Policy'] = tonbankcard_content_security_policy();
    }

    return $headers;
}

/**
 * Builds the public shell Content Security Policy.
 *
 * @return string
 */
function tonbankcard_content_security_policy() {
    $directives = [
        "default-src 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "form-action 'self'",
        "frame-ancestors 'self' https://web.telegram.org https://*.telegram.org",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://telegram.org https://cdn.jsdelivr.net https://unpkg.com https://changenow.io https://mc.yandex.ru https://mc.yandex.com https://mc.webvisor.com https://mc.webvisor.org https://yastatic.net",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
        "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com",
        "img-src 'self' data: blob: https://assets.coingecko.com https://*.coingecko.com https://changenow.io https://*.changenow.io https://mc.yandex.ru https://mc.yandex.com https://mc.webvisor.com https://mc.webvisor.org",
        "connect-src 'self' https://cdn.jsdelivr.net https://api.coingecko.com https://pro-api.coingecko.com https://api.groq.com https://*.upstash.io https://tonapi.io https://*.tonapi.io https://*.tonkeeper.com https://mc.yandex.ru https://mc.yandex.com https://mc.webvisor.com https://mc.webvisor.org wss://mc.yandex.ru wss://mc.yandex.com wss://mc.webvisor.com wss://mc.webvisor.org",
        "child-src 'self' blob: https://mc.yandex.ru https://mc.yandex.com https://mc.webvisor.com https://mc.webvisor.org",
        "frame-src 'self' blob: https://changenow.io https://*.changenow.io https://tonkeeper.com https://*.tonkeeper.com https://mc.yandex.ru https://mc.yandex.com https://mc.webvisor.com https://mc.webvisor.org",
        "worker-src 'self'",
        "manifest-src 'self'",
        "media-src 'self'",
    ];

    return implode( '; ', $directives );
}

/**
 * Emits configured security headers when PHP can still modify response headers.
 *
 * @param string $context html|api
 * @return void
 */
function tonbankcard_emit_security_headers( string $context = 'html' ) {
    if ( headers_sent() ) {
        return;
    }

    foreach ( tonbankcard_security_headers( $context ) as $name => $value ) {
        header( $name . ': ' . $value );
    }
}

/**
 * Converts a URL slug into readable title text.
 *
 * @param string $slug
 * @return string
 */
function tonbankcard_slug_title( string $slug ) {
    $known = [
        'bitcoin'       => 'Bitcoin',
        'ethereum'      => 'Ethereum',
        'toncoin'       => 'Toncoin',
        'tether'        => 'Tether',
        'usd-coin'      => 'USD Coin',
        'binancecoin'   => 'BNB',
        'the-open-network' => 'The Open Network',
    ];

    $slug = strtolower( trim( $slug ) );
    if ( isset( $known[ $slug ] ) ) {
        return $known[ $slug ];
    }

    $words = preg_split( '/[-_]+/', $slug );
    $words = array_filter( array_map( 'trim', is_array( $words ) ? $words : [] ) );
    if ( empty( $words ) ) {
        return 'Coin';
    }

    return ucwords( implode( ' ', $words ) );
}

/**
 * Matches a path against a route pattern with :parameters.
 *
 * @param string $pattern
 * @param string $path
 * @return array|null
 */
function tonbankcard_match_route_pattern( string $pattern, string $path ) {
    $pattern = tonbankcard_normalize_path( $pattern );
    $path    = tonbankcard_normalize_path( $path );

    $param_names = [];
    $regex = preg_replace_callback(
        '/\\\\?:([A-Za-z_][A-Za-z0-9_]*)/',
        function ( $matches ) use ( &$param_names ) {
            $param_names[] = $matches[1];
            return '([^/]+)';
        },
        preg_quote( $pattern, '#' )
    );

    if ( ! is_string( $regex ) || ! preg_match( '#^' . $regex . '$#', $path, $matches ) ) {
        return null;
    }

    $params = [];
    foreach ( $param_names as $index => $name ) {
        $params[ $name ] = rawurldecode( $matches[ $index + 1 ] );
    }

    return $params;
}

/**
 * Replaces :parameters in a route path.
 *
 * @param string $path
 * @param array $params
 * @return string
 */
function tonbankcard_fill_route_path( string $path, array $params ) {
    foreach ( $params as $name => $value ) {
        $path = str_replace( ':' . $name, rawurlencode( (string) $value ), $path );
    }
    return tonbankcard_normalize_path( $path );
}

/**
 * Applies a route subject to every %s placeholder in a metadata template.
 *
 * @param string $template
 * @param string $subject
 * @return string
 */
function tonbankcard_subject_template( string $template, string $subject ) {
    $placeholder_count = substr_count( $template, '%s' );
    if ( $placeholder_count < 1 ) {
        return $template;
    }

    return vsprintf( $template, array_fill( 0, $placeholder_count, $subject ) );
}

/**
 * Returns the V2 public route matching a path.
 *
 * @param string|null $path
 * @return array|null
 */
function tonbankcard_public_route_for_path( $path = null ) {
    $routes = isset( $GLOBALS['routes_v2']['public'] ) && is_array( $GLOBALS['routes_v2']['public'] )
        ? $GLOBALS['routes_v2']['public']
        : [];
    $path = null === $path ? tonbankcard_current_path() : tonbankcard_normalize_path( (string) $path );

    foreach ( $routes as $name => $route ) {
        if ( empty( $route['path'] ) || ! is_string( $route['path'] ) ) {
            continue;
        }

        $params = tonbankcard_match_route_pattern( $route['path'], $path );
        if ( null === $params ) {
            continue;
        }

        $route['name']   = $name;
        $route['params'] = $params;
        return $route;
    }

    return null;
}

/**
 * Builds server-rendered metadata for the current public route.
 *
 * @param string|null $path
 * @return array
 */
function tonbankcard_public_route_meta( $path = null ) {
    $route = tonbankcard_public_route_for_path( $path );
    $site  = $GLOBALS['site'];
    $path  = null === $path ? tonbankcard_current_path() : tonbankcard_normalize_path( (string) $path );

    if ( empty( $route ) ) {
        $route = [
            'name'        => 'home',
            'path'        => '/',
            'params'      => [],
            'title'       => $site['name'],
            'description' => $site['description'],
            'og_type'     => 'website',
            'schema_type' => 'WebPage',
        ];
    }

    $params      = isset( $route['params'] ) && is_array( $route['params'] ) ? $route['params'] : [];
    $subject     = isset( $params['id'] ) ? tonbankcard_slug_title( (string) $params['id'] ) : '';
    $title       = ! empty( $route['title'] ) ? $route['title'] : $site['title'];
    $description = ! empty( $route['description'] ) ? $route['description'] : $site['description'];

    if ( $subject && ! empty( $route['title_template'] ) ) {
        $title = tonbankcard_subject_template( $route['title_template'], $subject );
    }
    if ( $subject && ! empty( $route['description_template'] ) ) {
        $description = tonbankcard_subject_template( $route['description_template'], $subject );
    }

    $canonical_path = ! empty( $route['canonical_path'] ) ? $route['canonical_path'] : $path;
    $canonical_path = tonbankcard_fill_route_path( $canonical_path, $params );
    $canonical_url  = site_url( ltrim( $canonical_path, '/' ) );
    $full_title     = $title === $site['title'] ? $site['title'] : $title . ' - ' . $site['title'];

    return [
        'route'         => $route,
        'path'          => $path,
        'canonical_url' => $canonical_url,
        'title'         => $title,
        'full_title'    => $full_title,
        'description'   => $description,
        'og_type'       => ! empty( $route['og_type'] ) ? $route['og_type'] : 'website',
        'schema_type'   => ! empty( $route['schema_type'] ) ? $route['schema_type'] : 'WebPage',
        'subject'       => $subject,
        'robots'        => ! empty( $route['robots'] ) ? $route['robots'] : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
        'image'         => ! empty( $route['image'] ) ? $route['image'] : ( $site['og_image'] ?: $site['logo'] ),
        'image_alt'     => ! empty( $route['image_alt'] ) ? $route['image_alt'] : $site['name'] . ' Crypto Tracker market dashboard preview',
        'image_width'   => ! empty( $route['image_width'] ) ? $route['image_width'] : '1200',
        'image_height'  => ! empty( $route['image_height'] ) ? $route['image_height'] : '627',
    ];
}

/**
 * Returns external profile links used by Organization structured data.
 *
 * @return array
 */
function tonbankcard_public_same_as_links() {
    $configured_links = [];
    if ( ! empty( $GLOBALS['site']['same_as'] ) && is_array( $GLOBALS['site']['same_as'] ) ) {
        $configured_links = $GLOBALS['site']['same_as'];
    }

    $fallback_links = [
        'https://tonbankcard.com/',
        'https://t.me/tonbankcard',
        'https://t.me/tonbankcard_ru',
        'https://twitter.com/tonbankcard',
        'https://vk.com/tonbankcard',
        'https://www.youtube.com/@tonbankcard',
    ];

    $links = ! empty( $configured_links ) ? $configured_links : $fallback_links;
    return array_values( array_unique( array_filter( $links, function ( $url ) {
        return is_string( $url ) && tonbankcard_valid_absolute_url( $url );
    } ) ) );
}

/**
 * Returns a named public route definition.
 *
 * @param string $name
 * @return array|null
 */
function tonbankcard_public_route_named( string $name ) {
    $routes = isset( $GLOBALS['routes_v2']['public'] ) && is_array( $GLOBALS['routes_v2']['public'] )
        ? $GLOBALS['routes_v2']['public']
        : [];

    return isset( $routes[ $name ] ) && is_array( $routes[ $name ] ) ? $routes[ $name ] : null;
}

/**
 * Builds a canonical URL for a public route definition.
 *
 * @param array $route
 * @param array $params
 * @return string
 */
function tonbankcard_public_route_url( array $route, array $params = [] ) {
    $path = ! empty( $route['canonical_path'] ) ? $route['canonical_path'] : ( isset( $route['path'] ) ? $route['path'] : '/' );
    $path = tonbankcard_fill_route_path( (string) $path, $params );
    return site_url( ltrim( $path, '/' ) );
}

/**
 * Builds SiteNavigationElement objects from the public navigation config.
 *
 * @return array
 */
function tonbankcard_public_navigation_linked_data() {
    $items      = [];
    $navigation = ! empty( $GLOBALS['v2']['public_navigation'] ) && is_array( $GLOBALS['v2']['public_navigation'] )
        ? $GLOBALS['v2']['public_navigation']
        : [];

    foreach ( $navigation as $item ) {
        if ( empty( $item['route'] ) || ! is_string( $item['route'] ) ) {
            continue;
        }

        $route = tonbankcard_public_route_named( $item['route'] );
        if ( empty( $route['path'] ) || FALSE !== strpos( $route['path'], ':' ) ) {
            continue;
        }

        $items[] = [
            '@context' => 'https://schema.org',
            '@type'    => 'SiteNavigationElement',
            'position' => count( $items ) + 1,
            'name'     => ! empty( $item['text'] ) ? $item['text'] : $item['route'],
            'url'      => tonbankcard_public_route_url( $route ),
        ];
    }

    return $items;
}

/**
 * Builds breadcrumb structured-data items for public routes.
 *
 * @param array $meta
 * @return array
 */
function tonbankcard_public_breadcrumb_items( array $meta ) {
    $route = isset( $meta['route'] ) && is_array( $meta['route'] ) ? $meta['route'] : [];
    $name  = ! empty( $route['name'] ) ? $route['name'] : 'home';
    $items = [
        [
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Market Overview',
            'item'     => site_url(),
        ],
    ];

    if ( '/' === $meta['path'] ) {
        return $items;
    }

    if ( in_array( $name, [ 'coins', 'currency' ], TRUE ) ) {
        $markets = tonbankcard_public_route_named( 'markets' );
        if ( $markets ) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => count( $items ) + 1,
                'name'     => 'Crypto Markets',
                'item'     => tonbankcard_public_route_url( $markets ),
            ];
        }
    }

    if ( 'exchange' === $name ) {
        $exchanges = tonbankcard_public_route_named( 'exchanges' );
        if ( $exchanges ) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => count( $items ) + 1,
                'name'     => 'Crypto Exchanges',
                'item'     => tonbankcard_public_route_url( $exchanges ),
            ];
        }
    }

    $items[] = [
        '@type'    => 'ListItem',
        'position' => count( $items ) + 1,
        'name'     => $meta['subject'] ?: $meta['title'],
        'item'     => $meta['canonical_url'],
    ];

    return $items;
}

/**
 * Builds JSON-LD objects for the public website shell.
 *
 * @param array $meta
 * @return array
 */
function tonbankcard_public_linked_data( array $meta ) {
    $site = $GLOBALS['site'];
    $organization_id = site_url( '#organization' );
    $website_id      = site_url( '#website' );
    $webpage_id      = $meta['canonical_url'] . '#webpage';
    $image_url       = get_file_url_for_display( $meta['image'] );
    $data = [
        [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            '@id'      => $organization_id,
            'name'     => $site['name'],
            'url'      => site_url(),
            'logo'     => get_file_url_for_display( $site['logo'] ),
            'sameAs'   => tonbankcard_public_same_as_links(),
        ],
        [
            '@context'  => 'https://schema.org',
            '@type'     => 'WebSite',
            '@id'       => $website_id,
            'name'      => $site['name'],
            'url'       => site_url(),
            'publisher' => [
                '@id' => $organization_id,
            ],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => site_url( 'markets?q={search_term_string}' ),
                'query-input' => 'required name=search_term_string',
            ],
        ],
        [
            '@context' => 'https://schema.org',
            '@type'    => 'WebApplication',
            'name'     => $site['name'] . ' Crypto Tracker',
            'url'      => site_url(),
            'applicationCategory' => 'FinanceApplication',
            'operatingSystem'     => 'Web',
            'publisher' => [
                '@id' => $organization_id,
            ],
            'offers' => [
                '@type'         => 'Offer',
                'price'         => '0',
                'priceCurrency' => 'USD',
            ],
        ],
        [
            '@context'    => 'https://schema.org',
            '@type'       => $meta['schema_type'],
            '@id'         => $webpage_id,
            'name'        => $meta['title'],
            'description' => $meta['description'],
            'url'         => $meta['canonical_url'],
            'isPartOf'    => [
                '@id' => $website_id,
            ],
            'publisher'   => [
                '@id' => $organization_id,
            ],
            'breadcrumb'  => [
                '@id' => $meta['canonical_url'] . '#breadcrumb',
            ],
            'primaryImageOfPage' => [
                '@type'  => 'ImageObject',
                'url'    => $image_url,
                'width'  => $meta['image_width'],
                'height' => $meta['image_height'],
            ],
        ],
        [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            '@id'             => $meta['canonical_url'] . '#breadcrumb',
            'itemListElement' => tonbankcard_public_breadcrumb_items( $meta ),
        ],
    ];

    foreach ( tonbankcard_public_navigation_linked_data() as $navigation_item ) {
        $data[] = $navigation_item;
    }

    if ( 'FinancialProduct' === $meta['schema_type'] ) {
        $data[] = [
            '@context'    => 'https://schema.org',
            '@type'       => 'FinancialProduct',
            'name'        => $meta['subject'] ?: $meta['title'],
            'description' => $meta['description'],
            'url'         => $meta['canonical_url'],
            'category'    => 'Cryptocurrency',
        ];
    }

    return $data;
}

/**
 * Escapes XML text for sitemap output.
 *
 * @param string $value
 * @return string
 */
function tonbankcard_xml_escape( string $value ) {
    return htmlspecialchars( $value, ENT_XML1 | ENT_COMPAT, 'UTF-8' );
}

/**
 * Returns the runtime config array for SEO helpers, with a safe fallback.
 *
 * @return array
 */
function tonbankcard_seo_runtime_config() {
    if ( isset( $GLOBALS['runtime_config'] ) && is_array( $GLOBALS['runtime_config'] ) ) {
        return $GLOBALS['runtime_config'];
    }
    return function_exists( 'tonbankcard_runtime_config' ) ? tonbankcard_runtime_config() : [];
}

/**
 * Returns the API config array for SEO helpers, with a safe fallback.
 *
 * @return array
 */
function tonbankcard_seo_api_config() {
    return isset( $GLOBALS['api'] ) && is_array( $GLOBALS['api'] ) ? $GLOBALS['api'] : [];
}

/**
 * Emits a sitemap/SEO data-source degradation through the observability layer.
 *
 * @param string $level
 * @param string $event
 * @param array $context
 * @return void
 */
function tonbankcard_seo_log( string $level, string $event, array $context = [] ) {
    if ( function_exists( 'tonbankcard_observability_log' ) ) {
        tonbankcard_observability_log( tonbankcard_seo_runtime_config(), tonbankcard_seo_api_config(), $level, $event, $context );
    }
}

/**
 * Returns the supported language codes used for crawler hreflang signals.
 *
 * Derived from the translation registry so adding a language updates the
 * signals automatically.
 *
 * @return array<int,string>
 */
function tonbankcard_seo_languages() {
    $languages = array_keys( tonbankcard_supported_languages() );
    return array_values( array_filter( $languages, 'is_string' ) );
}

/**
 * Builds the localized variant URL for a canonical URL and language code.
 *
 * Uses the `?lang=` query-parameter strategy already honored by
 * tonbankcard_active_language(): the canonical URL carries no language
 * parameter (it is the x-default), while each localized alternate appends
 * `lang=<code>`. The strategy is shared by the head alternates and the
 * sitemap alternates so the signals stay consistent.
 *
 * @param string $canonical_url
 * @param string $language
 * @return string
 */
function tonbankcard_seo_localized_url( string $canonical_url, string $language ) {
    $language = tonbankcard_normalize_language( $language );

    $hash_parts = explode( '#', $canonical_url, 2 );
    $base       = $hash_parts[0];
    $fragment   = isset( $hash_parts[1] ) ? '#' . $hash_parts[1] : '';

    $separator = ( FALSE === strpos( $base, '?' ) ) ? '?' : '&';

    return $base . $separator . 'lang=' . rawurlencode( $language ) . $fragment;
}

/**
 * Returns hreflang alternates (head + sitemap) for a canonical URL.
 *
 * The default language (`en`) shares the canonical URL: the bare URL already
 * serves English content, so its alternate points at the canonical URL rather
 * than a `?lang=en` duplicate. This keeps every hreflang target on a canonical
 * URL and matches the `x-default` and `<link rel="canonical">` signals. Other
 * languages append `lang=<code>`.
 *
 * @param string $canonical_url
 * @return array<int,array{hreflang:string,href:string}>
 */
function tonbankcard_seo_hreflang_alternates( string $canonical_url ) {
    $alternates = [];
    $default    = tonbankcard_normalize_language( '' );

    foreach ( tonbankcard_seo_languages() as $language ) {
        $alternates[] = [
            'hreflang' => $language,
            'href'     => ( $language === $default )
                ? $canonical_url
                : tonbankcard_seo_localized_url( $canonical_url, $language ),
        ];
    }

    $alternates[] = [
        'hreflang' => 'x-default',
        'href'     => $canonical_url,
    ];

    return $alternates;
}

/**
 * Validates a CoinGecko-style identifier used in sitemap path segments.
 *
 * @param mixed $id
 * @return string Empty string when invalid.
 */
function tonbankcard_sitemap_safe_id( $id ) {
    $id = is_string( $id ) ? trim( $id ) : '';
    if ( '' === $id ) {
        return '';
    }
    return preg_match( '/^[A-Za-z0-9._-]{1,160}$/', $id ) ? $id : '';
}

/**
 * Loads the bundled, offline-safe SEO discovery universe.
 *
 * @return array{coins:array<int,string>,exchanges:array<int,string>}
 */
function tonbankcard_sitemap_bundled_universe() {
    static $universe = null;
    if ( null !== $universe ) {
        return $universe;
    }

    $universe = [ 'coins' => [], 'exchanges' => [] ];
    $file     = GECKO_CLIENT_CONFIG_DIR . '/seo-universe.php';
    if ( is_file( $file ) ) {
        $loaded = require $file;
        if ( is_array( $loaded ) ) {
            $universe['coins']     = isset( $loaded['coins'] ) && is_array( $loaded['coins'] ) ? $loaded['coins'] : [];
            $universe['exchanges'] = isset( $loaded['exchanges'] ) && is_array( $loaded['exchanges'] ) ? $loaded['exchanges'] : [];
        }
    }

    return $universe;
}

/**
 * Attempts to enumerate live identifiers from the market gateway.
 *
 * Guarded behind the TONBANKCARD_SITEMAP_LIVE_SOURCES flag and skipped on the
 * `local` profile so the test harness and offline self-hosters stay
 * deterministic. Any failure degrades to an empty list and is logged through
 * the observability layer, so the sitemap still renders with the bundled
 * universe.
 *
 * @param string $upstream_path
 * @param array $query
 * @param string $id_key
 * @return array<int,string>
 */
function tonbankcard_sitemap_live_ids( string $upstream_path, array $query, string $id_key = 'id' ) {
    if ( ! function_exists( 'tonbankcard_api_market_fetch' ) || ! function_exists( 'tonbankcard_api_market_provider_config' ) ) {
        return [];
    }
    if ( defined( 'TONBANKCARD_PROFILE' ) && 'local' === TONBANKCARD_PROFILE ) {
        return [];
    }
    if ( ! function_exists( 'tonbankcard_env_bool' ) || ! tonbankcard_env_bool( 'TONBANKCARD_SITEMAP_LIVE_SOURCES', FALSE ) ) {
        return [];
    }

    $runtime = tonbankcard_seo_runtime_config();
    $config  = tonbankcard_seo_api_config();

    try {
        $provider = tonbankcard_api_market_provider_config( $runtime, $config );
        $response = tonbankcard_api_market_fetch( $upstream_path, $query, $provider, $config );

        if ( ! empty( $response['error'] ) || empty( $response['body'] ) ) {
            tonbankcard_seo_log( 'warning', 'sitemap.live_source_unavailable', [
                'source' => $upstream_path,
                'error'  => isset( $response['error'] ) ? (string) $response['error'] : 'empty_body',
            ] );
            return [];
        }

        $decoded = json_decode( (string) $response['body'], TRUE );
        if ( ! is_array( $decoded ) ) {
            return [];
        }

        $ids = [];
        foreach ( $decoded as $row ) {
            if ( is_array( $row ) && isset( $row[ $id_key ] ) ) {
                $safe = tonbankcard_sitemap_safe_id( (string) $row[ $id_key ] );
                if ( '' !== $safe ) {
                    $ids[] = $safe;
                }
            }
        }
        return $ids;
    } catch ( Throwable $exception ) {
        tonbankcard_seo_log( 'error', 'sitemap.live_source_failed', [
            'source'  => $upstream_path,
            'message' => $exception->getMessage(),
        ] );
        return [];
    }
}

/**
 * Returns the indexable coin identifiers for `/coins/:id` sitemap URLs.
 *
 * Merges the live market gateway (when enabled/reachable) on top of the
 * bundled universe, and finally the hardcoded route params as a last-resort
 * fallback.
 *
 * @return array<int,string>
 */
function tonbankcard_sitemap_coin_ids() {
    $ids = [];

    foreach ( tonbankcard_sitemap_live_ids( 'coins/markets', [
        'vs_currency' => 'usd',
        'order'       => 'market_cap_desc',
        'per_page'    => 250,
        'page'        => 1,
    ] ) as $id ) {
        $ids[ $id ] = TRUE;
    }

    $bundled = tonbankcard_sitemap_bundled_universe();
    foreach ( $bundled['coins'] as $id ) {
        $safe = tonbankcard_sitemap_safe_id( $id );
        if ( '' !== $safe ) {
            $ids[ $safe ] = TRUE;
        }
    }

    $route = isset( $GLOBALS['routes_v2']['public']['coins'] ) && is_array( $GLOBALS['routes_v2']['public']['coins'] )
        ? $GLOBALS['routes_v2']['public']['coins']
        : [];
    if ( ! empty( $route['sitemap_params'] ) && is_array( $route['sitemap_params'] ) ) {
        foreach ( $route['sitemap_params'] as $params ) {
            if ( is_array( $params ) && isset( $params['id'] ) ) {
                $safe = tonbankcard_sitemap_safe_id( (string) $params['id'] );
                if ( '' !== $safe ) {
                    $ids[ $safe ] = TRUE;
                }
            }
        }
    }

    return array_keys( $ids );
}

/**
 * Returns the indexable exchange identifiers for `/exchange/:id` sitemap URLs.
 *
 * @return array<int,string>
 */
function tonbankcard_sitemap_exchange_ids() {
    $ids = [];

    foreach ( tonbankcard_sitemap_live_ids( 'exchanges/list', [] ) as $id ) {
        $ids[ $id ] = TRUE;
    }

    $bundled = tonbankcard_sitemap_bundled_universe();
    foreach ( $bundled['exchanges'] as $id ) {
        $safe = tonbankcard_sitemap_safe_id( $id );
        if ( '' !== $safe ) {
            $ids[ $safe ] = TRUE;
        }
    }

    return array_keys( $ids );
}

/**
 * Returns the indexable TON ecosystem asset detail paths for the sitemap.
 *
 * Reads the curated/discovered TON catalog (offline-safe defaults plus any
 * curation/admin store). Failures degrade to an empty list and are logged.
 *
 * @return array<int,string>
 */
function tonbankcard_sitemap_ton_paths() {
    if ( ! function_exists( 'tonbankcard_api_ton_load_state' ) ) {
        return [];
    }

    try {
        $state = tonbankcard_api_ton_load_state( tonbankcard_seo_runtime_config(), tonbankcard_seo_api_config() );
    } catch ( Throwable $exception ) {
        tonbankcard_seo_log( 'warning', 'sitemap.ton_catalog_unavailable', [
            'message' => $exception->getMessage(),
        ] );
        return [];
    }

    $assets = isset( $state['assets'] ) && is_array( $state['assets'] ) ? $state['assets'] : [];
    $paths  = [];
    $seen   = [];

    foreach ( $assets as $asset ) {
        if ( ! is_array( $asset ) ) {
            continue;
        }
        if ( array_key_exists( 'visible', $asset ) && empty( $asset['visible'] ) ) {
            continue;
        }

        $path = '';
        if ( isset( $asset['route']['path'] ) && is_string( $asset['route']['path'] ) ) {
            $path = $asset['route']['path'];
        } elseif ( isset( $asset['links']['web'] ) && is_string( $asset['links']['web'] ) ) {
            $path = $asset['links']['web'];
        }

        $path = trim( $path );
        if ( '' === $path || 0 !== strpos( $path, '/' ) ) {
            continue;
        }
        if ( isset( $seen[ $path ] ) ) {
            continue;
        }

        $seen[ $path ] = TRUE;
        $paths[]       = $path;
    }

    return $paths;
}

/**
 * Returns the best data-derived `<lastmod>` for dynamic sitemap sections.
 *
 * Reflects the freshness of the underlying TON/market catalog rather than
 * source-file mtimes.
 *
 * @return string Date in `Y-m-d` form.
 */
function tonbankcard_sitemap_data_lastmod() {
    $timestamp = 0;

    if ( function_exists( 'tonbankcard_api_ton_load_state' ) ) {
        try {
            $state = tonbankcard_api_ton_load_state( tonbankcard_seo_runtime_config(), tonbankcard_seo_api_config() );
            foreach ( [ 'updated_at', 'built_at' ] as $key ) {
                if ( ! empty( $state[ $key ] ) ) {
                    $candidate = strtotime( (string) $state[ $key ] );
                    if ( FALSE !== $candidate ) {
                        $timestamp = max( $timestamp, $candidate );
                    }
                }
            }
        } catch ( Throwable $exception ) {
            $timestamp = 0;
        }
    }

    if ( $timestamp <= 0 ) {
        $timestamp = time();
    }

    return gmdate( 'Y-m-d', $timestamp );
}

/**
 * Builds a single sitemap entry (with hreflang alternates) for a path.
 *
 * @param string $path
 * @param string $changefreq
 * @param string $priority
 * @param string $lastmod
 * @return array
 */
function tonbankcard_sitemap_make_entry( string $path, string $changefreq, string $priority, string $lastmod ) {
    $loc = site_url( ltrim( $path, '/' ) );

    return [
        'loc'        => $loc,
        'lastmod'    => $lastmod,
        'changefreq' => $changefreq,
        'priority'   => $priority,
        'alternates' => tonbankcard_seo_hreflang_alternates( $loc ),
    ];
}

/**
 * Returns the best available modification date for a sitemap route entry.
 *
 * @param array $route
 * @return string
 */
function tonbankcard_public_sitemap_lastmod( array $route ) {
    if ( ! empty( $route['sitemap_lastmod'] ) && is_string( $route['sitemap_lastmod'] ) ) {
        $timestamp = strtotime( $route['sitemap_lastmod'] );
        if ( FALSE !== $timestamp ) {
            return gmdate( 'Y-m-d', $timestamp );
        }
    }

    $files = [
        GECKO_CLIENT_CONFIG_DIR . '/routes-v2.php',
        GECKO_CLIENT_CONFIG_DIR . '/site.php',
    ];

    $route_name = ! empty( $route['name'] ) ? (string) $route['name'] : '';
    $template   = ! empty( $route['template'] ) ? (string) $route['template'] : $route_name;

    if ( 'home' === $template ) {
        $template = 'currencies';
    } elseif ( in_array( $template, [ 'coins', 'currency' ], TRUE ) ) {
        $template = 'currency';
    }

    if ( '' !== $template ) {
        $template_file = GECKO_CLIENT_TEMPLATES_DIR . '/routes/' . $template . '.php';
        if ( is_file( $template_file ) ) {
            $files[] = $template_file;
        }
    }

    $latest = 0;
    foreach ( $files as $file ) {
        $latest = max( $latest, file_modified_time( $file ) );
    }

    return gmdate( 'Y-m-d', $latest ?: time() );
}

/**
 * Returns indexable public route URLs for the XML sitemap.
 *
 * @return array
 */
function tonbankcard_public_sitemap_entries() {
    $routes = isset( $GLOBALS['routes_v2']['public'] ) && is_array( $GLOBALS['routes_v2']['public'] )
        ? $GLOBALS['routes_v2']['public']
        : [];

    $entries = [];
    $seen    = [];

    foreach ( $routes as $name => $route ) {
        if ( empty( $route['path'] ) || ! is_string( $route['path'] ) ) {
            continue;
        }
        if ( array_key_exists( 'sitemap', $route ) && FALSE === (bool) $route['sitemap'] ) {
            continue;
        }

        // Parameterized routes (coins, exchange, …) are enumerated dynamically
        // by their dedicated sitemap sections, not from hardcoded route params.
        if ( FALSE !== strpos( $route['path'], ':' ) ) {
            continue;
        }

        $route['name'] = $name;

        $path = ! empty( $route['canonical_path'] ) ? $route['canonical_path'] : $route['path'];
        $path = tonbankcard_fill_route_path( $path, [] );
        if ( FALSE !== strpos( $path, ':' ) ) {
            continue;
        }

        $loc = site_url( ltrim( $path, '/' ) );
        if ( isset( $seen[ $loc ] ) ) {
            continue;
        }

        $seen[ $loc ] = TRUE;
        $entries[] = [
            'loc'        => $loc,
            'lastmod'    => tonbankcard_public_sitemap_lastmod( $route ),
            'changefreq' => ! empty( $route['sitemap_changefreq'] ) ? $route['sitemap_changefreq'] : 'weekly',
            'priority'   => ! empty( $route['sitemap_priority'] ) ? $route['sitemap_priority'] : '0.5',
            'alternates' => tonbankcard_seo_hreflang_alternates( $loc ),
        ];
    }

    return $entries;
}

/**
 * Returns the indexable sitemap entries grouped by section.
 *
 * Sections: `pages` (static routes), `coins`, `exchanges`, `ton`. Each entry
 * carries hreflang alternates. Failures in any dynamic data source degrade
 * gracefully to an empty section (logged via the observability layer) so the
 * sitemap still renders with whatever sections are available.
 *
 * @return array<string,array<int,array>>
 */
function tonbankcard_public_sitemap_sections() {
    $data_lastmod = tonbankcard_sitemap_data_lastmod();

    $sections = [
        'pages'     => tonbankcard_public_sitemap_entries(),
        'coins'     => [],
        'exchanges' => [],
        'ton'       => [],
    ];

    foreach ( tonbankcard_sitemap_coin_ids() as $id ) {
        $sections['coins'][] = tonbankcard_sitemap_make_entry( '/coins/' . $id, 'hourly', '0.8', $data_lastmod );
    }

    foreach ( tonbankcard_sitemap_exchange_ids() as $id ) {
        $sections['exchanges'][] = tonbankcard_sitemap_make_entry( '/exchange/' . $id, 'daily', '0.6', $data_lastmod );
    }

    foreach ( tonbankcard_sitemap_ton_paths() as $path ) {
        $sections['ton'][] = tonbankcard_sitemap_make_entry( $path, 'daily', '0.6', $data_lastmod );
    }

    return $sections;
}

/**
 * Maps section sitemap filenames to their entries, paginating large sections
 * so no single file exceeds the sitemaps.org 50,000-URL limit.
 *
 * @return array<string,array<int,array>>
 */
function tonbankcard_public_sitemap_file_map() {
    $max = defined( 'TONBANKCARD_SITEMAP_MAX_URLS' ) ? (int) TONBANKCARD_SITEMAP_MAX_URLS : 50000;
    $max = $max > 0 ? $max : 50000;

    $defs = [
        'pages'     => 'sitemap-pages.xml',
        'coins'     => 'sitemap-coins.xml',
        'exchanges' => 'sitemap-exchanges.xml',
        'ton'       => 'sitemap-ton.xml',
    ];

    $sections = tonbankcard_public_sitemap_sections();
    $files    = [];

    foreach ( $defs as $key => $basename ) {
        $entries = isset( $sections[ $key ] ) ? $sections[ $key ] : [];
        if ( empty( $entries ) ) {
            continue;
        }

        $chunks = array_chunk( $entries, $max );
        if ( count( $chunks ) <= 1 ) {
            $files[ $basename ] = $entries;
            continue;
        }

        $prefix = preg_replace( '/\.xml$/', '', $basename );
        foreach ( $chunks as $index => $chunk ) {
            $files[ $prefix . '-' . ( $index + 1 ) . '.xml' ] = $chunk;
        }
    }

    return $files;
}

/**
 * Renders a `<urlset>` document for a list of sitemap entries.
 *
 * @param array<int,array> $entries
 * @return string
 */
function tonbankcard_sitemap_render_urlset( array $entries ) {
    $lines = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">',
    ];

    foreach ( $entries as $entry ) {
        $lines[] = '    <url>';
        $lines[] = '        <loc>' . tonbankcard_xml_escape( $entry['loc'] ) . '</loc>';
        if ( ! empty( $entry['alternates'] ) && is_array( $entry['alternates'] ) ) {
            foreach ( $entry['alternates'] as $alternate ) {
                $lines[] = '        <xhtml:link rel="alternate" hreflang="'
                    . tonbankcard_xml_escape( $alternate['hreflang'] ) . '" href="'
                    . tonbankcard_xml_escape( $alternate['href'] ) . '"/>';
            }
        }
        $lines[] = '        <lastmod>' . tonbankcard_xml_escape( $entry['lastmod'] ) . '</lastmod>';
        $lines[] = '        <changefreq>' . tonbankcard_xml_escape( $entry['changefreq'] ) . '</changefreq>';
        $lines[] = '        <priority>' . tonbankcard_xml_escape( $entry['priority'] ) . '</priority>';
        $lines[] = '    </url>';
    }

    $lines[] = '</urlset>';
    return implode( "\n", $lines ) . "\n";
}

/**
 * Renders the sitemap index referencing every section sitemap file.
 *
 * @return string
 */
function tonbankcard_public_sitemap_index_xml() {
    $lastmod = tonbankcard_sitemap_data_lastmod();

    $lines = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ];

    foreach ( array_keys( tonbankcard_public_sitemap_file_map() ) as $name ) {
        $lines[] = '    <sitemap>';
        $lines[] = '        <loc>' . tonbankcard_xml_escape( site_url( $name ) ) . '</loc>';
        $lines[] = '        <lastmod>' . tonbankcard_xml_escape( $lastmod ) . '</lastmod>';
        $lines[] = '    </sitemap>';
    }

    $lines[] = '</sitemapindex>';
    return implode( "\n", $lines ) . "\n";
}

/**
 * Renders the combined XML sitemap (every section in one `<urlset>`).
 *
 * Kept for backwards compatibility: `/sitemap.xml` still serves the full set
 * of indexable URLs (pages + coins + exchanges + TON), now with hreflang
 * alternates. Large installs should prefer `/sitemap_index.xml`.
 *
 * @return string
 */
function tonbankcard_public_sitemap_xml() {
    $entries = [];
    foreach ( tonbankcard_public_sitemap_sections() as $section ) {
        foreach ( $section as $entry ) {
            $entries[] = $entry;
        }
    }

    return tonbankcard_sitemap_render_urlset( $entries );
}

/**
 * Returns the renderer for a requested sitemap path (index, section, or the
 * combined document), or NULL when the path is not a sitemap.
 *
 * @param string $path Request path, e.g. `/sitemap-coins.xml`.
 * @return callable|null
 */
function tonbankcard_public_sitemap_renderer_for( string $path ) {
    $name = ltrim( $path, '/' );

    if ( 'sitemap.xml' === $name ) {
        return 'tonbankcard_public_sitemap_xml';
    }

    if ( 'sitemap_index.xml' === $name ) {
        return 'tonbankcard_public_sitemap_index_xml';
    }

    if ( preg_match( '/^sitemap-[A-Za-z0-9_-]+\.xml$/', $name ) ) {
        $files = tonbankcard_public_sitemap_file_map();
        if ( isset( $files[ $name ] ) ) {
            $entries = $files[ $name ];
            return static function () use ( $entries ) {
                return tonbankcard_sitemap_render_urlset( $entries );
            };
        }
    }

    return null;
}

/**
 * Renders a sitemap document, caching it in the shared Upstash Redis store
 * when caching is enabled (TTL + stale window). Falls back to rendering on
 * every request when the cache is disabled/unavailable.
 *
 * @param string $name Cache-key suffix / sitemap filename.
 * @param callable $renderer
 * @return string
 */
function tonbankcard_sitemap_cached_render( string $name, callable $renderer ) {
    if ( ! function_exists( 'tonbankcard_api_cache_get' ) || ! function_exists( 'tonbankcard_api_cache_set' ) ) {
        return (string) $renderer();
    }

    $runtime = tonbankcard_seo_runtime_config();
    $config  = tonbankcard_seo_api_config();
    $key     = 'tonbankcard:sitemap:' . $name;

    $hit = tonbankcard_api_cache_get( $runtime, $config, $key );
    if ( in_array( $hit['state'], [ 'hit', 'stale' ], TRUE ) && isset( $hit['entry']['data']['xml'] ) ) {
        return (string) $hit['entry']['data']['xml'];
    }

    $xml = (string) $renderer();

    $ttl       = defined( 'TONBANKCARD_SITEMAP_CACHE_TTL' ) ? (int) TONBANKCARD_SITEMAP_CACHE_TTL : 3600;
    $stale_ttl = defined( 'TONBANKCARD_SITEMAP_CACHE_STALE_TTL' ) ? (int) TONBANKCARD_SITEMAP_CACHE_STALE_TTL : 86400;
    if ( $ttl > 0 ) {
        tonbankcard_api_cache_set( $runtime, $config, $key, [ 'xml' => $xml ], [], $ttl, $stale_ttl, time() );
    }

    return $xml;
}

/**
 * Sends an XML sitemap response with conditional-request support.
 *
 * Emits a content-hash ETag and a data-derived Last-Modified header, and
 * answers `If-None-Match` / `If-Modified-Since` with `304 Not Modified` so
 * crawlers can revalidate cheaply.
 *
 * @param string $xml
 * @return void
 */
function tonbankcard_sitemap_send( string $xml ) {
    $etag          = '"' . md5( $xml ) . '"';
    $last_modified = gmdate( 'D, d M Y H:i:s', strtotime( tonbankcard_sitemap_data_lastmod() . ' 00:00:00 UTC' ) ?: time() ) . ' GMT';

    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'Cache-Control: public, max-age=3600' );
    header( 'ETag: ' . $etag );
    header( 'Last-Modified: ' . $last_modified );

    $if_none_match     = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? trim( (string) $_SERVER['HTTP_IF_NONE_MATCH'] ) : '';
    $if_modified_since = isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? trim( (string) $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) : '';

    $etag_match     = '' !== $if_none_match && ( $if_none_match === $etag || '*' === $if_none_match );
    $modified_match = '' !== $if_modified_since && @strtotime( $if_modified_since ) >= ( @strtotime( $last_modified ) ?: 0 );

    if ( $etag_match || ( '' === $if_none_match && $modified_match ) ) {
        http_response_code( 304 );
        exit;
    }

    echo $xml;
}

/**
 * Renders robots.txt with the dynamic sitemap location.
 *
 * @return string
 */
function tonbankcard_public_robots_txt() {
    return implode(
        "\n",
        [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /api/',
            'Disallow: /database/',
            'Disallow: /dev/',
            'Disallow: /install/',
            'Clean-param: utm_source&utm_medium&utm_campaign&utm_term&utm_content&yclid&gclid&fbclid /',
            'Sitemap: ' . site_url( 'sitemap_index.xml' ),
            'Sitemap: ' . site_url( 'sitemap.xml' ),
            '',
        ]
    );
}

/**
 * Renders the TON Connect manifest with runtime-aware absolute URLs.
 *
 * @return string
 */
function tonbankcard_ton_connect_manifest() {
    $manifest = [
        'url'             => site_url(),
        'name'            => ! empty( $GLOBALS['site']['name'] ) ? $GLOBALS['site']['name'] . ' Crypto Tracker' : 'TONBANKCARD Crypto Tracker',
        'iconUrl'         => image_url( 'tonbankcard-icon-512x512.png' ),
        'termsOfUseUrl'   => site_url( 'terms' ),
        'privacyPolicyUrl' => site_url( 'privacy-policy' ),
    ];

    return json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
}

/**
 * Sends crawler assets that need runtime-aware canonical URLs.
 *
 * @return void
 */
function tonbankcard_dispatch_public_seo_assets() {
    $path = tonbankcard_current_path();

    if ( '/robots.txt' === $path ) {
        header( 'Content-Type: text/plain; charset=UTF-8' );
        echo tonbankcard_public_robots_txt();
        exit;
    }

    if ( '/tonconnect-manifest.json' === $path ) {
        header( 'Content-Type: application/json; charset=UTF-8' );
        header( 'Cache-Control: public, max-age=3600' );
        echo tonbankcard_ton_connect_manifest();
        exit;
    }

    $renderer = tonbankcard_public_sitemap_renderer_for( $path );
    if ( null !== $renderer ) {
        $name = ltrim( $path, '/' );
        tonbankcard_sitemap_send( tonbankcard_sitemap_cached_render( $name, $renderer ) );
        exit;
    }
}

/**
 * Returns TRUE when the path is a public static asset managed by the app.
 *
 * @param string $path
 * @param array $performance
 * @return bool
 */
function tonbankcard_static_asset_is_cacheable( string $path, array $performance = [] ) {
    $settings = isset( $performance['static_assets'] ) && is_array( $performance['static_assets'] )
        ? $performance['static_assets']
        : [];
    $extensions = isset( $settings['extensions'] ) && is_array( $settings['extensions'] )
        ? $settings['extensions']
        : [ 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'webmanifest', 'woff', 'woff2', 'ttf', 'eot' ];

    $extension = strtolower( pathinfo( parse_url( $path, PHP_URL_PATH ) ?: $path, PATHINFO_EXTENSION ) );

    return '' !== $extension && in_array( $extension, $extensions, TRUE );
}

/**
 * Returns a static asset MIME type for local router responses.
 *
 * @param string $path
 * @return string
 */
function tonbankcard_static_asset_content_type( string $path ) {
    $extension = strtolower( pathinfo( parse_url( $path, PHP_URL_PATH ) ?: $path, PATHINFO_EXTENSION ) );
    $types = [
        'css'         => 'text/css; charset=UTF-8',
        'js'          => 'application/javascript; charset=UTF-8',
        'mjs'         => 'application/javascript; charset=UTF-8',
        'json'        => 'application/json; charset=UTF-8',
        'webmanifest' => 'application/manifest+json; charset=UTF-8',
        'svg'         => 'image/svg+xml',
        'png'         => 'image/png',
        'jpg'         => 'image/jpeg',
        'jpeg'        => 'image/jpeg',
        'gif'         => 'image/gif',
        'ico'         => 'image/x-icon',
        'woff'        => 'font/woff',
        'woff2'       => 'font/woff2',
        'ttf'         => 'font/ttf',
        'eot'         => 'application/vnd.ms-fontobject',
    ];

    return isset( $types[ $extension ] ) ? $types[ $extension ] : 'application/octet-stream';
}

/**
 * Builds production cache headers for app-managed static assets.
 *
 * @param string $request_uri
 * @param array $performance
 * @return array
 */
function tonbankcard_static_asset_cache_headers( string $request_uri, array $performance = [] ) {
    $settings = isset( $performance['static_assets'] ) && is_array( $performance['static_assets'] )
        ? $performance['static_assets']
        : [];
    $immutable_max_age = isset( $settings['immutable_max_age_seconds'] ) ? (int) $settings['immutable_max_age_seconds'] : 31536000;
    $unversioned_max_age = isset( $settings['unversioned_max_age_seconds'] ) ? (int) $settings['unversioned_max_age_seconds'] : 86400;
    $stale_while_revalidate = isset( $settings['stale_while_revalidate_seconds'] ) ? (int) $settings['stale_while_revalidate_seconds'] : 604800;
    $manifest_max_age = isset( $settings['manifest_max_age_seconds'] ) ? (int) $settings['manifest_max_age_seconds'] : 3600;
    $service_worker_cache_control = isset( $settings['service_worker_cache_control'] )
        ? (string) $settings['service_worker_cache_control']
        : 'no-cache, no-store, must-revalidate';

    $path = parse_url( $request_uri, PHP_URL_PATH );
    if ( FALSE === $path || null === $path || '' === $path ) {
        $path = $request_uri;
    }
    $query_string = parse_url( $request_uri, PHP_URL_QUERY );
    $query = [];
    if ( FALSE !== $query_string && null !== $query_string && '' !== $query_string ) {
        parse_str( $query_string, $query );
    }

    $basename = strtolower( basename( $path ) );
    $extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
    $headers = [
        'X-Content-Type-Options' => 'nosniff',
    ];

    if ( 'service-worker.js' === $basename ) {
        $headers['Cache-Control'] = $service_worker_cache_control;
        $headers['Service-Worker-Allowed'] = '/';
        return $headers;
    }

    if ( 'webmanifest' === $extension || 'manifest.json' === $basename ) {
        $headers['Cache-Control'] = 'public, max-age=' . max( 0, $manifest_max_age ) . ', stale-while-revalidate=' . max( 0, $stale_while_revalidate );
        $headers['Vary'] = 'Accept-Encoding';
        return $headers;
    }

    if ( in_array( $extension, [ 'html', 'htm' ], TRUE ) ) {
        $headers['Cache-Control'] = 'no-cache';
        return $headers;
    }

    $versioned = isset( $query['t'] ) || isset( $query['v'] ) || 1 === preg_match( '/[._-][a-f0-9]{8,}\./i', $path );
    if ( $versioned ) {
        $headers['Cache-Control'] = 'public, max-age=' . max( 0, $immutable_max_age ) . ', immutable';
    } else {
        $headers['Cache-Control'] = 'public, max-age=' . max( 0, $unversioned_max_age ) . ', stale-while-revalidate=' . max( 0, $stale_while_revalidate );
    }
    $headers['Vary'] = 'Accept-Encoding';

    return $headers;
}

/**
 * Emits static asset cache headers.
 *
 * @param string $request_uri
 * @param array $performance
 * @return void
 */
function tonbankcard_emit_static_asset_headers( string $request_uri, array $performance = [] ) {
    foreach ( tonbankcard_static_asset_cache_headers( $request_uri, $performance ) as $name => $value ) {
        header( $name . ': ' . $value );
    }
}

/**
 * @since 1.0.0
 * Generates stylesheet URL
 *
 * @param string $path
 * @return string
 */
function css_url( string $path = '' ) {
    return site_url( 'assets/css/' . ltrim( $path, '/' ) );
}

/**
 * @since 1.0.0
 * Generates image URL
 *
 * @param string $path
 * @return string
 */
function image_url( string $path = '' ) {
    return site_url( 'assets/images/' . ltrim( $path, '/' ) );
}

/**
 * @since 1.0.0
 * Generates javascript URL
 *
 * @param string $path
 * @return string
 */
function js_url( string $path = '' ) {
    return site_url( 'assets/js/' . ltrim( $path, '/' ) );
}

/**
 * @since 1.0.0
 * Generates image URL
 *
 * @param string $path
 * @return string
 */
function vendor_url( string $path = '' ) {
    return site_url( 'assets/vendor/' . ltrim( $path, '/' ) );
}

/**
 * @since 1.0.0
 * Checks if string is URL with http or https protocols
 *
 * @param string $url
 * @return bool
 */
function is_http( string $url ) {
    return !!preg_match( '/^https?:\/\//', $url );
}

/**
 * @since 1.0.0
 * Tries to return a file absolute URL with modified timestamp query param (prevent cache)
 *
 * @param string $url
 * @return string|null
 */
function get_file_url_for_display( $url ) {
    if ( ! is_string( $url ) ) {
        return null;
    }

    if ( ! is_http( $url ) ) { // relative path
        $rel_path = $url;

        // has query part?
        $queryPos = strpos( $url, '?' );
        if ( $queryPos !== false ) {
            $rel_path = substr( $url, 0, $queryPos );
        }

        $path = GECKO_CLIENT_DIR . '/' . ltrim( $rel_path, '/' );
        $modified_timestamp = file_modified_time( $path );
        // append "t" param with timestamp to query
        $queryString = ( $queryPos === false ? '?' : '&' ) . 't=' . $modified_timestamp;
        // absolute url
        return site_url( $url . $queryString );
    }
    // absolute url
    return $url;
}

/**
 * @since 1.0.0
 * Gets the base value for Vue Router
 *
 * @return string
 */
function router_base() {
    $path = parse_url( base_url(), PHP_URL_PATH ) ?: '';
    return rtrim( $path, '/' ) . '/';
}

/**
 * @since 1.0.0
 * Escapes string for using in HTML attribute value
 *
 * @param string $text
 * @return string
 */
function esc_attr( $text ) {
    if ( ! is_string( $text ) || '' === $text ) {
        return $text;
    }
    return htmlspecialchars( $text, ENT_COMPAT | ENT_HTML5, 'UTF-8', false );
}

/**
 * @since 1.0.0
 * Escapes URL string
 *
 * @param string $url
 * @return string
 */
function esc_url( $url ) {
    if ( ! is_string( $url ) || '' === $url ) {
        return $url;
    }
    // encode spaces
    $url = str_replace( ' ', '%20', ltrim( $url ) );
    // remove invalid chars
    return preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url );
}

/**
 * @since 1.0.0
 * Escapes string for safe content in HTML context
 *
 * @param string $text
 * @return string
 */
function esc_html( $text ) {
    if ( ! is_string( $text ) || '' === $text ) {
        return $text;
    }
    return htmlspecialchars( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false );
}

/**
 * @since 1.0.0
 * Gets text translation or itself if missing
 *
 * @param string $text
 * @return string
 */
function __( string $text ) {
    if ( isset( $GLOBALS['translation'][ $text ] ) && is_string( $GLOBALS['translation'][ $text ] ) && '' !== $GLOBALS['translation'][ $text ] ) {
        return $GLOBALS['translation'][ $text ];
    }
    return $text;
}

/**
 * Returns the supported public languages keyed by ISO 639-1 code.
 *
 * The values are the language's endonym (native name) used in the
 * language switcher UI.
 *
 * @return array<string,string>
 */
function tonbankcard_supported_languages() {
    return [
        'en' => 'English',
        'ru' => 'Русский',
        'fr' => 'Français',
        'ar' => 'العربية',
        'zh' => '中文',
    ];
}

/**
 * Reports whether a language is rendered right-to-left.
 *
 * @param string $language
 * @return bool
 */
function tonbankcard_language_is_rtl( string $language ) {
    return in_array( strtolower( $language ), [ 'ar', 'he', 'fa', 'ur' ], true );
}

/**
 * Normalizes a raw language tag to a supported ISO 639-1 code.
 *
 * Accepts values like `en`, `EN`, `en-US`, `ru_RU`. Falls back to `en`
 * when the input is empty or not in the supported list.
 *
 * @param mixed $language
 * @param array<string,mixed>|null $supported Optional override of the supported map.
 * @return string
 */
function tonbankcard_normalize_language( $language, $supported = null ) {
    $code = strtolower( trim( (string) $language ) );
    if ( '' === $code ) {
        return 'en';
    }

    $code = str_replace( '_', '-', $code );
    if ( false !== strpos( $code, '-' ) ) {
        $code = substr( $code, 0, strpos( $code, '-' ) );
    }

    if ( null === $supported ) {
        $supported = tonbankcard_supported_languages();
    }

    return isset( $supported[ $code ] ) ? $code : 'en';
}

/**
 * Picks the best matching language from the browser `Accept-Language` header.
 *
 * Honours quality factors (`q=`) when present and falls back to the order
 * of declared languages otherwise.
 *
 * @param string|null $header
 * @param array<string,mixed>|null $supported
 * @return string|null Returns null when no supported language matches.
 */
function tonbankcard_language_from_accept_header( $header, $supported = null ) {
    if ( ! is_string( $header ) || '' === trim( $header ) ) {
        return null;
    }

    if ( null === $supported ) {
        $supported = tonbankcard_supported_languages();
    }

    $candidates = [];
    foreach ( explode( ',', $header ) as $index => $part ) {
        $part = trim( $part );
        if ( '' === $part ) {
            continue;
        }

        $quality = 1.0;
        if ( false !== strpos( $part, ';' ) ) {
            list( $tag, $params ) = array_pad( explode( ';', $part, 2 ), 2, '' );
            $tag = trim( $tag );
            if ( preg_match( '/q\s*=\s*([0-9.]+)/i', (string) $params, $matches ) ) {
                $quality = (float) $matches[1];
            }
        } else {
            $tag = $part;
        }

        if ( '' === $tag ) {
            continue;
        }

        $code = tonbankcard_normalize_language( $tag, $supported );
        // tonbankcard_normalize_language returns 'en' for unsupported codes.
        // Re-check that the original tag actually maps to a supported entry.
        $base = strtolower( str_replace( '_', '-', trim( $tag ) ) );
        if ( false !== strpos( $base, '-' ) ) {
            $base = substr( $base, 0, strpos( $base, '-' ) );
        }
        if ( ! isset( $supported[ $base ] ) ) {
            continue;
        }

        // Stable sort by quality desc, then declared order asc.
        $candidates[] = [ 'code' => $code, 'quality' => $quality, 'order' => $index ];
    }

    if ( empty( $candidates ) ) {
        return null;
    }

    usort( $candidates, function ( $a, $b ) {
        if ( $a['quality'] === $b['quality'] ) {
            return $a['order'] - $b['order'];
        }
        return ( $a['quality'] < $b['quality'] ) ? 1 : -1;
    } );

    return $candidates[0]['code'];
}

/**
 * Resolves the active language for the current request.
 *
 * Resolution order:
 *  1. `lang` query parameter when it points to a supported language.
 *  2. `tbc_lang` cookie.
 *  3. Browser `Accept-Language` header.
 *  4. Default `en`.
 *
 * The chosen language is cached on the function call so repeated invocations
 * stay consistent during a single request.
 *
 * @param array<string,mixed>|null $translations Loaded dictionary map.
 * @return string
 */
function tonbankcard_active_language( $translations = null ) {
    static $cached = null;
    if ( null !== $cached ) {
        return $cached;
    }

    $supported = tonbankcard_supported_languages();
    if ( is_array( $translations ) ) {
        $supported = array_intersect_key( $supported, $translations );
        if ( empty( $supported ) ) {
            $supported = [ 'en' => 'English' ];
        }
    }

    if ( ! empty( $_GET['lang'] ) ) {
        $code = tonbankcard_normalize_language( $_GET['lang'], $supported );
        if ( isset( $supported[ $code ] ) ) {
            $cached = $code;
            return $cached;
        }
    }

    if ( ! empty( $_COOKIE['tbc_lang'] ) ) {
        $code = tonbankcard_normalize_language( $_COOKIE['tbc_lang'], $supported );
        if ( isset( $supported[ $code ] ) ) {
            $cached = $code;
            return $cached;
        }
    }

    $accept = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
    $accepted = tonbankcard_language_from_accept_header( $accept, $supported );
    if ( null !== $accepted ) {
        $cached = $accepted;
        return $cached;
    }

    $cached = 'en';
    return $cached;
}

/**
 * Persists the visitor's language preference in a long-lived cookie.
 *
 * @param string $language
 * @return string The normalized language that was saved.
 */
function tonbankcard_set_language_cookie( string $language ) {
    $code = tonbankcard_normalize_language( $language );

    if ( ! headers_sent() ) {
        $params = [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'samesite' => 'Lax',
        ];

        if ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) ) {
            $params['secure'] = true;
        }

        setcookie( 'tbc_lang', $code, $params );
    }

    $_COOKIE['tbc_lang'] = $code;

    return $code;
}

/**
 * Handles the public locale-set endpoint at `/api/locale/set`.
 *
 * The request must come from the same origin (its `Referer` host must match
 * the current `Host`), and the `next` parameter or `Referer` is used as the
 * post-redirect target. Both are constrained to relative paths on the same
 * origin to prevent open-redirect abuse. The endpoint persists the chosen
 * language in the `tbc_lang` cookie and exits.
 *
 * @return void
 */
function tonbankcard_handle_locale_set_request() {
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = parse_url( $request_uri, PHP_URL_PATH );
    if ( '/api/locale/set' !== $path ) {
        return;
    }

    $language = isset( $_GET['lang'] ) ? (string) $_GET['lang'] : '';
    if ( '' !== $language ) {
        tonbankcard_set_language_cookie( $language );
    }

    $next = '/';
    if ( isset( $_GET['next'] ) && is_string( $_GET['next'] ) && '' !== $_GET['next'] ) {
        $candidate = tonbankcard_safe_redirect_path( $_GET['next'] );
        if ( null !== $candidate ) {
            $next = $candidate;
        }
    } elseif ( isset( $_SERVER['HTTP_REFERER'] ) && is_string( $_SERVER['HTTP_REFERER'] ) ) {
        $candidate = tonbankcard_safe_redirect_path( $_SERVER['HTTP_REFERER'] );
        if ( null !== $candidate ) {
            $next = $candidate;
        }
    }

    if ( ! headers_sent() ) {
        header( 'Cache-Control: no-store' );
        header( 'Location: ' . $next, true, 302 );
    }
    exit;
}

/**
 * Validates a redirect target so it can only point back at the current host
 * as a relative path. Returns the path-with-query on success or null when the
 * target is unsafe.
 *
 * @param string $target
 * @return string|null
 */
function tonbankcard_safe_redirect_path( string $target ) {
    $target = trim( $target );
    if ( '' === $target ) {
        return null;
    }

    // Plain relative path: must start with `/` but not `//` (protocol-relative).
    if ( '/' === $target[0] && ( strlen( $target ) < 2 || '/' !== $target[1] ) ) {
        return $target;
    }

    $parts = parse_url( $target );
    if ( false === $parts || empty( $parts['host'] ) ) {
        return null;
    }

    $expected = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
    if ( '' === $expected ) {
        return null;
    }

    $candidate = strtolower( $parts['host'] );
    if ( ! empty( $parts['port'] ) ) {
        $candidate .= ':' . (int) $parts['port'];
    }

    // Accept either a bare host match or host:port match against HTTP_HOST.
    if ( $candidate !== $expected && $candidate !== strtok( $expected, ':' ) ) {
        return null;
    }

    $path = isset( $parts['path'] ) && '' !== $parts['path'] ? $parts['path'] : '/';
    if ( ! empty( $parts['query'] ) ) {
        $path .= '?' . $parts['query'];
    }
    return $path;
}

/**
 * @since 1.0.0
 * Prints HTML attribute
 *
 * @param string $attr
 * @param mixed $value
 * @param bool $bool
 */
function attr( string $attr, $value, $bool = true ) {
    if ( $bool ) {
        printf( ' %s="%s"', $attr, esc_attr( $value ) );
    }
}

/**
 * @since 1.0.0
 * Prints or returns link attributes (route or href)
 *
 * @param array $link
 * @param array $attrs
 * @param bool $echo
 * @return void|string
 */
function link_attrs( $link, array $attrs = [], bool $echo = true ) {
    if ( ! empty( $link['route'] ) ) {
        if ( is_string( $link['route'] ) ) {
            $attrs[] = sprintf( ':to="{name:\'%s\'}"', esc_attr( $link['route'] ) );
            $attrs[] = 'exact';
        } elseif ( is_array( $link['route'] ) && ! empty( $link['route']['name'] ) ) {
            if ( empty( $link['route']['params'] ) ) {
                $attrs[] = sprintf( ':to="{name:\'%s\'}"', esc_attr( $link['route']['name'] ) );
            } else {
                $params = [];
                foreach ( $link['route']['params'] as $param => $value ) {
                    $params[] = sprintf( "'%s':'%s'", esc_attr( $param ), esc_attr( $value ) );
                }
                $attrs[] = sprintf( ':to="{name:\'%s\',params:{%s}}"', esc_attr( $link['route']['name'] ), implode( $params, ',' ) );
            }
            $attrs[] = 'exact';
        }
    } elseif ( ! empty( $link['url'] ) ) {
        $attrs[] = sprintf( 'href="%s"', esc_url( $link['url'] ) );

        if ( ! empty( $link['external'] ) ) {
            $attrs[] = 'target="_blank"';
            $attrs[] = 'rel="noopener"';
        }
    }

    $attrs =  implode( ' ', $attrs );
    if ( $echo ) {
        echo $attrs;
    } else {
        return $attrs;
    }
}

/**
 * @since 1.0.0
 * Prints or returns link attributes (route or href)
 *
 * @param string $route
 * @param array $params
 * @param bool $echo
 * @return void|string
 */
function to_attr(string $route, array $params = [], bool $echo = true ) {
    if ( empty( $params ) ) {
        $attr = sprintf( ':to="{name:\'%s\'}"', esc_attr( $route ) );
    } else {
        $_params = [];
        foreach ( $params as $param => $value ) {
            $_params[] = sprintf( "'%s':'%s'", esc_attr( $param ), esc_attr( $value ) );
        }
        $attr = sprintf( ':to="{name:\'%s\'},params:{%s}"', esc_attr( $route ), implode( $params, ',' ) );
    }

    if ( $echo ) {
        echo $attr;
    } else {
        return $attr;
    }
}

/**
 * Builds a configuration error entry for an environment variable.
 *
 * @param string $name
 * @param string $message
 * @param string $example
 * @return array
 */
function tonbankcard_env_error( string $name, string $message, string $example = "'value'" ) {
    return [
        'file'    => '.env or server environment',
        'config'  => $name,
        'message' => $message,
        'example' => $example,
    ];
}

/**
 * Checks whether a URL is absolute HTTP(S).
 *
 * @param string $url
 * @return bool
 */
function tonbankcard_valid_absolute_url( string $url ) {
    $parts = parse_url( $url );
    return ! empty( $parts['scheme'] )
        && ! empty( $parts['host'] )
        && in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], TRUE );
}

/**
 * Adds a missing environment variable error.
 *
 * @param array $invalid
 * @param string $name
 * @param string $message
 * @param string $example
 */
function tonbankcard_require_env( array &$invalid, string $name, string $message, string $example = "'<set in deployment secret store>'" ) {
    $value = tonbankcard_env( $name, '' );
    if ( '' === trim( (string) $value ) ) {
        $invalid[] = tonbankcard_env_error( $name, $message, $example );
    }
}

/**
 * Adds an invalid or missing URL environment variable error.
 *
 * @param array $invalid
 * @param string $name
 * @param string $url
 * @param string $message
 * @param string $example
 */
function tonbankcard_require_url( array &$invalid, string $name, string $url, string $message, string $example ) {
    if ( '' === trim( $url ) || ! tonbankcard_valid_absolute_url( $url ) ) {
        $invalid[] = tonbankcard_env_error( $name, $message, $example );
    }
}

/**
 * Returns TRUE when an environment value is a supported boolean string.
 *
 * @param string $name
 * @return bool
 */
function tonbankcard_env_bool_is_valid( string $name ) {
    if ( ! tonbankcard_env_has( $name ) ) {
        return TRUE;
    }

    $value = strtolower( trim( (string) tonbankcard_env( $name, '' ) ) );
    return in_array( $value, [ '1', '0', 'true', 'false', 'yes', 'no', 'on', 'off' ], TRUE );
}

/**
 * Returns TRUE when an environment value is a supported integer range.
 *
 * @param string $name
 * @param int $min
 * @param int $max
 * @return bool
 */
function tonbankcard_env_int_is_valid( string $name, int $min, int $max ) {
    if ( ! tonbankcard_env_has( $name ) ) {
        return TRUE;
    }

    $value = trim( (string) tonbankcard_env( $name, '' ) );
    if ( '' === $value || ! preg_match( '/^-?[0-9]+$/', $value ) ) {
        return FALSE;
    }

    $int_value = (int) $value;
    return $int_value >= $min && $int_value <= $max;
}

/**
 * Returns TRUE when an environment value is a comma-separated allowed list.
 *
 * @param string $name
 * @param array $allowed
 * @return bool
 */
function tonbankcard_env_list_is_valid( string $name, array $allowed ) {
    if ( ! tonbankcard_env_has( $name ) ) {
        return TRUE;
    }

    $value = trim( (string) tonbankcard_env( $name, '' ) );
    if ( '' === $value ) {
        return FALSE;
    }

    foreach ( explode( ',', $value ) as $item ) {
        if ( ! in_array( strtolower( trim( $item ) ), $allowed, TRUE ) ) {
            return FALSE;
        }
    }

    return TRUE;
}

/**
 * @since 1.0.0
 * Returns invalid constants
 *
 * @return array
 */
function validate_constants() {
    $invalid = [];

    if ( 'production' !== GECKO_CLIENT_ENV  && 'development' !== GECKO_CLIENT_ENV ) {
        $invalid[] = [
            'file'     => 'constants.php',
            'constant' => 'GECKO_CLIENT_ENV',
            'message'  => "Enter 'production' or 'development'. For live websites choose 'production'.",
            'example'  => "'production'"
        ];
    }

    if ( ! in_array( TONBANKCARD_PROFILE, [ 'local', 'staging', 'production', 'telegram' ], TRUE ) ) {
        $invalid[] = [
            'file'     => '.env or server environment',
            'constant' => 'TONBANKCARD_PROFILE',
            'message'  => "Enter 'local', 'staging', 'production', or 'telegram'.",
            'example'  => "'production'"
        ];
    }

    return $invalid;
}

/**
 * Returns invalid runtime environment configuration.
 *
 * @return array
 */
function validate_runtime_config() {
    $invalid = [];
    $runtime = isset( $GLOBALS['runtime_config'] ) ? $GLOBALS['runtime_config'] : tonbankcard_runtime_config();
    $profile = isset( $runtime['profile'] ) ? $runtime['profile'] : 'local';

    switch ( $profile ) {
        case 'local':
            tonbankcard_require_url(
                $invalid,
                'TONBANKCARD_LOCAL_BASE_URL',
                $runtime['urls']['local'],
                'Enter a valid local absolute URL.',
                "'http://localhost:8888/'"
            );
            break;
        case 'staging':
            tonbankcard_require_url(
                $invalid,
                'TONBANKCARD_STAGING_BASE_URL',
                $runtime['urls']['staging'],
                'Enter the public staging absolute URL.',
                "'https://staging-marketcap.tonbankcard.com/'"
            );
            break;
        case 'production':
            tonbankcard_require_url(
                $invalid,
                'TONBANKCARD_PUBLIC_BASE_URL',
                $runtime['urls']['public'],
                'Enter the production public website absolute URL.',
                "'https://marketcap.tonbankcard.com/'"
            );
            break;
        case 'telegram':
            tonbankcard_require_url(
                $invalid,
                'TONBANKCARD_TELEGRAM_BASE_URL',
                $runtime['urls']['telegram'],
                'Enter the Telegram Mini App absolute URL.',
                "'https://miniapp.tonbankcard.com/'"
            );
            tonbankcard_require_url(
                $invalid,
                'TONBANKCARD_PUBLIC_BASE_URL',
                $runtime['urls']['public'],
                'Enter the public website absolute URL used by shared links and fallbacks.',
                "'https://marketcap.tonbankcard.com/'"
            );
            break;
    }

    $feature_flags = [
        'TONBANKCARD_FEATURE_AI',
        'TONBANKCARD_FEATURE_ALERTS',
        'TONBANKCARD_FEATURE_CHANGENOW',
        'TONBANKCARD_FEATURE_TON_CONNECT',
        'TONBANKCARD_FEATURE_REFERRALS',
        'TONBANKCARD_FEATURE_GAMIFICATION',
        'TONBANKCARD_FEATURE_PREMIUM',
    ];

    foreach ( array_merge( $feature_flags, [ 'TONBANKCARD_VERBOSE_TRACING', 'TONBANKCARD_CLIENT_ERROR_REPORTING' ] ) as $flag ) {
        if ( ! tonbankcard_env_bool_is_valid( $flag ) ) {
            $invalid[] = tonbankcard_env_error(
                $flag,
                "Enter a boolean value: 'true' or 'false'.",
                "'false'"
            );
        }
    }

    $observability_log_level = strtolower( trim( (string) tonbankcard_env( 'TONBANKCARD_OBSERVABILITY_LOG_LEVEL', 'warning' ) ) );
    if ( ! in_array( $observability_log_level, [ 'debug', 'info', 'warning', 'warn', 'error', 'critical', 'off' ], TRUE ) ) {
        $invalid[] = tonbankcard_env_error(
            'TONBANKCARD_OBSERVABILITY_LOG_LEVEL',
            "Enter 'debug', 'info', 'warning', 'error', 'critical', or 'off'.",
            "'warning'"
        );
    }

    $coingecko_plan = strtolower( trim( (string) tonbankcard_env( 'COINGECKO_API_PLAN', 'demo' ) ) );
    if ( ! in_array( $coingecko_plan, [ 'demo', 'pro' ], TRUE ) ) {
        $invalid[] = tonbankcard_env_error(
            'COINGECKO_API_PLAN',
            "Enter 'demo' for CoinGecko Public/Demo API or 'pro' for CoinGecko Pro API.",
            "'demo'"
        );
    }
    if ( 'pro' === $coingecko_plan && empty( $runtime['providers']['coingecko']['api_key_configured'] ) ) {
        $invalid[] = tonbankcard_env_error(
            'COINGECKO_API_KEY',
            'Set the CoinGecko Pro API key when COINGECKO_API_PLAN is pro. The value is not exposed to browser JavaScript.'
        );
    }

    $ai_provider = strtolower( trim( (string) tonbankcard_env( 'TONBANKCARD_AI_PROVIDER', 'groq' ) ) );
    if ( ! in_array( $ai_provider, [ 'groq' ], TRUE ) ) {
        $invalid[] = tonbankcard_env_error(
            'TONBANKCARD_AI_PROVIDER',
            "Enter 'groq'. Future providers can be added behind this selector without changing calling code.",
            "'groq'"
        );
    }

    $ai_fallback_behavior = strtolower( trim( (string) tonbankcard_env( 'TONBANKCARD_AI_FALLBACK_BEHAVIOR', 'unavailable' ) ) );
    if ( ! in_array( $ai_fallback_behavior, [ 'unavailable' ], TRUE ) ) {
        $invalid[] = tonbankcard_env_error(
            'TONBANKCARD_AI_FALLBACK_BEHAVIOR',
            "Enter 'unavailable' so provider failures degrade to a safe unavailable state.",
            "'unavailable'"
        );
    }

    if ( ! tonbankcard_env_list_is_valid( 'TONBANKCARD_AI_ENABLED_FEATURES', [ 'summary', 'sentiment', 'insight' ] ) ) {
        $invalid[] = tonbankcard_env_error(
            'TONBANKCARD_AI_ENABLED_FEATURES',
            'Enter a comma-separated subset of summary, sentiment, and insight.',
            "'summary,sentiment,insight'"
        );
    }

    if ( tonbankcard_env_has( 'GROQ_MODEL_ID' ) && ! preg_match( '/^[A-Za-z0-9._\/:-]{1,160}$/', (string) tonbankcard_env( 'GROQ_MODEL_ID', '' ) ) ) {
        $invalid[] = tonbankcard_env_error(
            'GROQ_MODEL_ID',
            'Enter a safe Groq model id.',
            "'llama-3.3-70b-versatile'"
        );
    }

    if ( ! tonbankcard_valid_absolute_url( $runtime['providers']['groq']['base_url'] ) ) {
        $invalid[] = tonbankcard_env_error(
            'GROQ_BASE_URL',
            'Enter the Groq OpenAI-compatible API root URL.',
            "'https://api.groq.com/openai/v1/'"
        );
    }

    foreach ( [
        'GROQ_TIMEOUT_SECONDS'           => [ 1, 120, "'10'" ],
        'GROQ_RATE_LIMIT_WINDOW_SECONDS' => [ 1, 3600, "'60'" ],
        'GROQ_RATE_LIMIT_MAX_REQUESTS'   => [ 1, 10000, "'20'" ],
        'TONBANKCARD_ALERT_MAX_RULES_PER_USER' => [ 1, 200, "'20'" ],
        'TONBANKCARD_ALERT_DEFAULT_FREQUENCY_CAP_SECONDS' => [ 300, 86400, "'3600'" ],
        'TONBANKCARD_ALERT_MAX_DELIVERIES_PER_DAY' => [ 1, 100, "'8'" ],
        'TONBANKCARD_ALERT_EVALUATION_INTERVAL_SECONDS' => [ 60, 3600, "'300'" ],
        'TONBANKCARD_ACHIEVEMENT_WEEKLY_CHECK_DAYS' => [ 2, 30, "'7'" ],
        'TONBANKCARD_ACHIEVEMENT_SHARE_MILESTONE_COUNT' => [ 1, 100, "'3'" ],
        'TONBANKCARD_ACHIEVEMENT_MAX_PROMPTS_PER_SESSION' => [ 1, 6, "'1'" ],
    ] as $name => $bounds ) {
        if ( ! tonbankcard_env_int_is_valid( $name, $bounds[0], $bounds[1] ) ) {
            $invalid[] = tonbankcard_env_error(
                $name,
                'Enter an integer in the supported range.',
                $bounds[2]
            );
        }
    }

    if ( tonbankcard_env_has( 'TONBANKCARD_ALERT_WORKER_TOKEN' )
        && '' !== trim( (string) tonbankcard_env( 'TONBANKCARD_ALERT_WORKER_TOKEN', '' ) )
        && ! preg_match( '/^[A-Za-z0-9._:-]{12,160}$/', (string) tonbankcard_env( 'TONBANKCARD_ALERT_WORKER_TOKEN', '' ) )
    ) {
        $invalid[] = tonbankcard_env_error(
            'TONBANKCARD_ALERT_WORKER_TOKEN',
            'Enter a safe alert worker token with at least 12 characters.',
            "'alerts-worker-token-rotate-me'"
        );
    }

    if ( 'local' !== $profile ) {
        foreach ( $feature_flags as $flag ) {
            if ( ! tonbankcard_env_has( $flag ) ) {
                $invalid[] = tonbankcard_env_error(
                    $flag,
                    "Set an explicit production feature flag value: 'true' or 'false'.",
                    "'false'"
                );
            }
        }

        tonbankcard_require_env(
            $invalid,
            'TONBANKCARD_BOT_USERNAME',
            'Set the Telegram bot username used for Mini App and shared-link entry points.',
            "'tonbankcard_bot'"
        );
        tonbankcard_require_env(
            $invalid,
            'UPSTASH_REDIS_REST_URL',
            'Set the Upstash Redis REST URL used by cache and rate-limit services.',
            "'https://example.upstash.io'"
        );
        tonbankcard_require_env(
            $invalid,
            'UPSTASH_REDIS_REST_TOKEN',
            'Set the Upstash Redis REST token. The value is not exposed to browser JavaScript.'
        );
        tonbankcard_require_env(
            $invalid,
            'MYSQL_DSN',
            'Set the MySQL or MariaDB DSN used by server-side persistence.',
            "'mysql:host=127.0.0.1;dbname=marketcap;charset=utf8mb4'"
        );
        tonbankcard_require_env(
            $invalid,
            'MYSQL_USER',
            'Set the MySQL or MariaDB application user.',
            "'marketcap'"
        );
        tonbankcard_require_env(
            $invalid,
            'MYSQL_PASSWORD',
            'Set the MySQL or MariaDB application password. The value is not displayed.'
        );
    }

    if ( 'telegram' === $profile || ! empty( $runtime['feature_flags']['alerts'] ) ) {
        tonbankcard_require_env(
            $invalid,
            'TONBANKCARD_BOT_TOKEN',
            'Set the Telegram bot token for trusted Mini App sessions and bot delivery. The value is not displayed.'
        );
    }

    if ( ! empty( $runtime['feature_flags']['ai'] ) ) {
        tonbankcard_require_env(
            $invalid,
            'GROQ_API_KEY',
            'Set the Groq API key when AI features are enabled. The value is not exposed to browser JavaScript.'
        );
    }

    if ( ! empty( $runtime['feature_flags']['changenow'] ) ) {
        tonbankcard_require_env(
            $invalid,
            'CHANGENOW_LINK_ID',
            'Set the ChangeNOW partner link id when the exchange widget is enabled.',
            "'f300d9f2b6f88e'"
        );
    }

    return $invalid;
}

/**
 * @since 1.0.0
 * Returns invalid site configs
 *
 * @return array
 */
function validate_site_configs() {
    $invalid = [];

    // $site['base_url']
    $base_url_parsed = parse_url( (string) base_url() );
    if ( empty( $base_url_parsed ) || empty( $base_url_parsed['scheme'] ) || empty( $base_url_parsed['host'] ) ) {
        switch ( TONBANKCARD_PROFILE ) {
            case 'local':
                $invalid[] = [
                    'file'    => 'config/site.php',
                    'config'  => '$site[\'base_url\'][\'local\']',
                    'message' => 'Enter a valid local absolute URL through TONBANKCARD_LOCAL_BASE_URL.',
                    'example' => "'http://localhost:8888/'"
                ];
                break;
            case 'staging':
                $invalid[] = [
                    'file'    => 'config/site.php',
                    'config'  => '$site[\'base_url\'][\'staging\']',
                    'message' => 'Enter a valid staging absolute URL through TONBANKCARD_STAGING_BASE_URL.',
                    'example' => "'https://staging-marketcap.tonbankcard.com/'"
                ];
                break;
            case 'production':
                $invalid[] = [
                    'file'    => 'config/site.php',
                    'config'  => '$site[\'base_url\'][\'production\']',
                    'message' => 'Enter a valid public absolute URL through TONBANKCARD_PUBLIC_BASE_URL.',
                    'example' => "'https://marketcap.tonbankcard.com/'"
                ];
                break;
            case 'telegram':
                $invalid[] = [
                    'file'    => 'config/site.php',
                    'config'  => '$site[\'base_url\'][\'telegram\']',
                    'message' => 'Enter a valid Telegram Mini App absolute URL through TONBANKCARD_TELEGRAM_BASE_URL.',
                    'example' => "'https://miniapp.tonbankcard.com/'"
                ];
                break;
        }
    }

    // $site['lang']
    if ( empty( $GLOBALS['site']['lang'] ) || ! is_string( $GLOBALS['site']['lang'] ) ) {
        $invalid[] = [
            'file'    => 'config/site.php',
            'config'  => '$site[\'lang\']',
            'message' => 'Enter a valid language locale.',
            'example' => "'en'"
        ];
    }
    // $site['name']
    if ( empty( $GLOBALS['site']['name'] ) || ! is_string( $GLOBALS['site']['name'] ) ) {
        $invalid[] = [
            'file'    => 'config/site.php',
            'config'  => '$site[\'name\']',
            'message' => 'Enter a non empty string.',
            'example' => "'Gecko Client'"
        ];
    }
    // $site['title']
    if ( empty( $GLOBALS['site']['title'] ) || ! is_string( $GLOBALS['site']['title'] ) ) {
        $invalid[] = [
            'file'    => 'config/site.php',
            'config'  => '$site[\'title\']',
            'message' => 'Enter a non empty string.',
            'example' => "'Gecko Client - Cryptocurrency Markets'"
        ];
    }

    return $invalid;
}

/**
 * @since 1.0.0
 * Validates Vuetify configurations and returns any invalid configs
 *
 * @return array
 */
function validate_vuetify_configs() {
    $invalid = [];

    // $vuetify['default_theme']
    $default_theme = isset( $GLOBALS['vuetify']['default_theme'] ) ? $GLOBALS['vuetify']['default_theme'] : '';
    if ( $default_theme !== 'light' && $default_theme !== 'dark' ) {
        $invalid[] = [
            'file'    => 'config/vuetify.php',
            'config'  => '$vuetify[\'default_theme\']',
            'message' => "Enter a valid theme: 'light' or 'dark'.",
            'example' => "'light'"
        ];
    }

    return $invalid;
}

/**
 * @since 1.0.0
 * Builds Vuetify constructor configuration options
 *
 * @return array
 */
function vuetify_constructor_options() {
    $lang = $GLOBALS['site']['lang'];

    return [
        'rtl' => (bool) $GLOBALS['site']['rtl'],
        'theme' => [
            'dark' => 'dark' === $GLOBALS['vuetify']['default_theme'],
            'themes' => [
                'light' => $GLOBALS['vuetify']['light_theme'],
                'dark' => $GLOBALS['vuetify']['dark_theme'],
            ],
        ],
        'lang' => [
            'current' => $lang,
            'locales' => [
                $lang => $GLOBALS['vuetify']['translation'],
            ],
        ],
    ];
}

/**
 * @since 1.0.0
 * Gets selectable VS currencies
 *
 * @return array[]
 */
function get_enabled_supported_vs_currencies() {
    $enabled = [];
    foreach ( $GLOBALS['coingecko']['supported_vs_currencies'] as $currency ) {
        if ( ! empty( $currency['enabled'] ) ) {
            unset( $currency['enabled'] );
            $enabled[] = $currency;
        }
    }
    return $enabled;
}

/**
 * @since 1.0.0
 * Gets enabled routes
 *
 * @return array[]
 */
function get_enabled_routes() {
    $required = [
        'currencies',
        'currency',
        'exchanges',
        'exchange'
    ];
    $enabled = [];
    foreach ( $GLOBALS['routes'] as $name => $route ) {
        if ( in_array( $name, $required ) || ! empty( $route['enabled'] ) ) {
            unset( $route['enabled'] );
            $enabled[ $name ] = $route;
        }
    }
    return $enabled;
}
