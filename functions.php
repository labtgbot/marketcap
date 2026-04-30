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

    foreach ( $routes as $route ) {
        if ( empty( $route['path'] ) || ! is_string( $route['path'] ) ) {
            continue;
        }
        if ( array_key_exists( 'sitemap', $route ) && FALSE === (bool) $route['sitemap'] ) {
            continue;
        }

        $params_list = [ [] ];
        if ( FALSE !== strpos( $route['path'], ':' ) ) {
            if ( empty( $route['sitemap_params'] ) || ! is_array( $route['sitemap_params'] ) ) {
                continue;
            }
            $params_list = $route['sitemap_params'];
        }

        foreach ( $params_list as $params ) {
            $params = is_array( $params ) ? $params : [];
            $path = ! empty( $route['canonical_path'] ) ? $route['canonical_path'] : $route['path'];
            $path = tonbankcard_fill_route_path( $path, $params );
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
                'changefreq' => ! empty( $route['sitemap_changefreq'] ) ? $route['sitemap_changefreq'] : 'weekly',
                'priority'   => ! empty( $route['sitemap_priority'] ) ? $route['sitemap_priority'] : '0.5',
            ];
        }
    }

    return $entries;
}

/**
 * Renders the XML sitemap for public website routes.
 *
 * @return string
 */
function tonbankcard_public_sitemap_xml() {
    $lines = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ];

    foreach ( tonbankcard_public_sitemap_entries() as $entry ) {
        $lines[] = '    <url>';
        $lines[] = '        <loc>' . tonbankcard_xml_escape( $entry['loc'] ) . '</loc>';
        $lines[] = '        <changefreq>' . tonbankcard_xml_escape( $entry['changefreq'] ) . '</changefreq>';
        $lines[] = '        <priority>' . tonbankcard_xml_escape( $entry['priority'] ) . '</priority>';
        $lines[] = '    </url>';
    }

    $lines[] = '</urlset>';
    return implode( "\n", $lines ) . "\n";
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
            'Disallow: /api/',
            'Disallow: /database/',
            'Disallow: /dev/',
            'Sitemap: ' . site_url( 'sitemap.xml' ),
            '',
        ]
    );
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

    if ( '/sitemap.xml' === $path ) {
        header( 'Content-Type: application/xml; charset=UTF-8' );
        echo tonbankcard_public_sitemap_xml();
        exit;
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
    if ( isset( $GLOBALS['translation'][ $text ] ) && is_string( $GLOBALS['translation'][ $text ] ) ) {
        return $GLOBALS['translation'][ $text ];
    }
    return $text;
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
    ] as $name => $bounds ) {
        if ( ! tonbankcard_env_int_is_valid( $name, $bounds[0], $bounds[1] ) ) {
            $invalid[] = tonbankcard_env_error(
                $name,
                'Enter an integer in the supported range.',
                $bounds[2]
            );
        }
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
            "'3cc0024a18fd9d'"
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
