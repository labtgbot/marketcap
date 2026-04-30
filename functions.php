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
    $base_url_parsed = parse_url( base_url() );
    if ( empty( $base_url_parsed ) || empty( $base_url_parsed['scheme'] ) || empty( $base_url_parsed['host'] ) ) {
        switch ( GECKO_CLIENT_ENV ) {
            case 'production':
                $invalid[] = [
                    'file'    => 'config/site.php',
                    'config'  => '$site[\'base_url\'][\'production\']',
                    'message' => 'Enter a valid public absolute URL.',
                    'example' => "'https://my-domain.tld'"
                ];
                break;
            case 'development':
                $invalid[] = [
                    'file'    => 'config/site.php',
                    'config'  => '$site[\'base_url\'][\'development\']',
                    'message' => 'Enter a valid absolute URL.',
                    'example' => "'http://localhost:8888/gecko-client/'"
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