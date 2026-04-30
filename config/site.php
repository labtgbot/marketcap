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

/*
| -------------------------------------------------------------------------
| BASE URL
| -------------------------------------------------------------------------
|
| TYPE: array
|
| DESCRIPTION:
| This is used for creating absolute URLs and "Vue Router" base setting.
| If used inside subdirectories in your domain director, it should contain
| the relative path like "/subdirectory".
|
| Add one URL for each environment:
| - production: for live website
| - development: for development environment
|
| -------------------------------------------------------------------------
| EXAMPLE FOR ROOT
| -------------------------------------------------------------------------
|
| $site['base_url']['production']  = 'https://my-domain.tld/';
| $site['base_url']['development'] = 'http://localhost:8888/';
|
| -------------------------------------------------------------------------
| EXAMPLE FOR SUBDIRECTORY
| -------------------------------------------------------------------------
|
| $site['base_url']['production']  = 'https://my-domain.tld/gecko-client/';
| $site['base_url']['development'] = 'http://localhost:8888/gecko-client/';
|
*/
$site['base_url']['production'] = '';

$site['base_url']['development'] = 'http://localhost:8888/gecko-client/';

/*
| -------------------------------------------------------------------------
| LANG
| -------------------------------------------------------------------------
|
| TYPE: string
|
| DESCRIPTION:
| Defines the language used for this website content.
|
| A language tag is made of hyphen-separated language subtags,
| where each subtag indicates a certain property of the language.
|
| The 3 most common subtags are:
|
| 1. Language subtag (Required)
|   A 2-or-3-character code that defines the basic language, typically written in all lowercase.
|   For example, the language code for English is 'en', and the code for Badeshi is 'bdz'.
|
| 2. Script subtag (Optional)
|   This subtag defines the writing system used for the language,
|   and is always 4 characters long, with the first letter capitalized.
|   For example, French-in-Braille is 'fr-Brai' and 'ja-Kana' is Japanese written with the Katakana alphabet.
|   If the language is written in a highly typical way, like English in the Latin alphabet,
|   there is no need to use this subtag.
|
| 3. Region subtag (Optional)
|   This subtag defines a dialect of the base language from a particular location,
|   and is either 2 letters in ALLCAPS matching a country code, or 3 numbers matching a non-country area.
|   For example, 'es-ES' is for Spanish as spoken in Spain, and 'es-013' is Spanish as spoken in Central America.
|   “International Spanish” would just be 'es'.
|
| REFERENCE:
| https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/lang
|
*/
$site['lang'] = 'en';

/*
| -------------------------------------------------------------------------
| RIGHT-TO-LEFT
| -------------------------------------------------------------------------
|
| TYPE: bool
|
| DESCRIPTION:
| Indicating that text is written from right to left.
| For example, the he locale (Hebrew) specifies right-to-left.
| Arabic (ar) is another common language written RTL.
|
*/
$site['rtl'] = FALSE;

/*
| -------------------------------------------------------------------------
| NAME
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Short name for the website.
| MAX LENGTH: 18 (to fit navigation)
|
*/
$site['name'] = 'Gecko Client';

/*
| -------------------------------------------------------------------------
| TITLE
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Text used for title tag, OpenGraph (og:title) and Twitter Card (twitter:title).
| MAX LENGTH: 70
|
*/
$site['title'] = 'Gecko Client - Cryptocurrency Markets';

/*
| -------------------------------------------------------------------------
| DESCRIPTION
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Text used for description meta tag, OpenGraph (og:description) and Twitter Card (twitter:description).
| MAX LENGTH: 200
|
*/
$site['description'] = 'The Best Cryptocurrency Daily Financial Information Portal';

/*
| -------------------------------------------------------------------------
| THEME COLOR
| -------------------------------------------------------------------------
|
| TYPE: string
|
| DESCRIPTION:
| Color value for "theme-color" meta tag. Some browsers will use this
| to customize the display of the page or of the surrounding user interface.
|
| REFERENCE:
| https://developer.mozilla.org/en-US/docs/Web/HTML/Element/meta/name/theme-color
|
*/
$site['theme_color'] = '#1976D2';

/*
| -------------------------------------------------------------------------
| LOGO
| -------------------------------------------------------------------------
|
| TYPE: string
|
| DESCRIPTION:
| Absolute or relative URL for the logo used in top bar and navigation header.
| Use a square image larger than 64x64 pixels.
|
| IMAGE TYPE: PNG (Recommended).
|
| -------------------------------------------------------------------------
| EXAMPLE
| -------------------------------------------------------------------------
|
| $site['logo'] = 'assets/images/logo.png';
|
*/
$site['logo'] = 'assets/images/logo.png';


/*
| -------------------------------------------------------------------------
| FAVICON
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Absolute or relative URL for the "favicon.ico". Is optional if you set "ICONS".
| IMAGE TYPE: ICO (Required)
|
| -------------------------------------------------------------------------
| EXAMPLE
| -------------------------------------------------------------------------
|
| $site['favicon'] = 'assets/images/favicon.ico';
|
*/
$site['favicon'] = 'assets/images/favicon.ico';

/*
| -------------------------------------------------------------------------
| ICONS
| -------------------------------------------------------------------------
|
| TYPE: array
| DESCRIPTION: Modern browser favicon images. Should provide at least "16x16" and "32x32" icons.
| IMAGE TYPE: PNG (Required)
| KEY: "{width}x{height}"
| VALUE: Absolute or relative URL
|
| -------------------------------------------------------------------------
| EXAMPLE
| -------------------------------------------------------------------------
|
| $site['icons'] = [
|     '16x16' => 'assets/images/favicon-16x16.png',
|     '32x32' => 'assets/images/favicon-32x32.png',
| ];
|
*/
$site['icons'] = [
    '16x16'   => 'assets/images/favicon-16x16.png',
    '32x32'   => 'assets/images/favicon-32x32.png',
    '192x192' => 'assets/images/android-chrome-192x192.png',
];

/*
| -------------------------------------------------------------------------
| APPLE TOUCH ICONS
| -------------------------------------------------------------------------
|
| TYPE: array
| DESCRIPTION: Apple specific logos images. Size 180x180 for minimal configuration.
| IMAGE TYPE: PNG (Required)
| KEY: "{width}x{height}"
| VALUE: Absolute or relative URL
|
| REFERENCE:
| https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariWebContent/ConfiguringWebApplications/ConfiguringWebApplications.html
|
| -------------------------------------------------------------------------
| SIZES
| -------------------------------------------------------------------------
|
|   120x120   For iPhone
|   152x152   For iPad
|   167x167   For iPad Pro
|   180x180   For iPhone
|
| -------------------------------------------------------------------------
| EXAMPLE
| -------------------------------------------------------------------------
|
| $site['apple_touch_icons'] = [
|     '120x120' => 'assets/images/apple-touch-icon-120x120.png',
|     '152x152' => 'assets/images/apple-touch-icon-152x152.png',
|     '167x167' => 'assets/images/apple-touch-icon-167x167.png',
|     '180x180' => 'assets/images/apple-touch-icon-180x180.png',
| ];
|
*/
$site['apple_touch_icons'] = [
    '180x180' => 'assets/images/apple-touch-icon.png',
];

/*
| -------------------------------------------------------------------------
| OPEN GRAPH IMAGE
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Absolute or relative URL for the Open Graph image (og:image).
| IMAGE TYPE: JPEG, PNG or GIF
| RECOMMENDED SIZE: 1200x627
|
| REFERENCE:
| https://ogp.me/
| https://developers.facebook.com/docs/sharing/webmasters/#images
|
| -------------------------------------------------------------------------
| EXAMPLE
| -------------------------------------------------------------------------
|
| $site['og_image'] = 'assets/images/og_image.png';
|
*/
$site['og_image'] = '';

/*
| -------------------------------------------------------------------------
| TWITTER CARD
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Twitter Card type (twitter:card) can be 'summary' or 'summary_large_image'.
|
| REFERENCE:
| https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards
|
*/
$site['twitter_card'] = 'summary';

/*
| -------------------------------------------------------------------------
| TWITTER SITE
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Twitter Card handle for the website (twitter:site).
|
| REFERENCE:
| https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/markup
|
| -------------------------------------------------------------------------
| EXAMPLE
| -------------------------------------------------------------------------
|
| $site['twitter_site'] = '@Website';
|
*/
$site['twitter_site'] = '';

/*
| -------------------------------------------------------------------------
| TWITTER CREATOR
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Twitter Card handle for the author (twitter:creator).
|
| REFERENCE:
| https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/markup
|
| -------------------------------------------------------------------------
| EXAMPLE
| -------------------------------------------------------------------------
|
| $site['twitter_creator'] = '@Author';
|
*/
$site['twitter_creator'] = '';

/*
| -------------------------------------------------------------------------
| TWITTER IMAGE
| -------------------------------------------------------------------------
|
| TYPE: string
| DESCRIPTION: Absolute or relative URL for the Twitter Card image (twitter:image).
| IMAGE TYPE: JPG, PNG, WEBP or GIF
| Recommended Size: 800x418
|
| REFERENCE:
| https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/markup
|
| -------------------------------------------------------------------------
| EXAMPLE
| -------------------------------------------------------------------------
|
| $site['twitter_image'] = 'assets/images/twitter_image.png';
|
*/
$site['twitter_image'] = '';

/*
| -------------------------------------------------------------------------
| DISCLAIMER
| -------------------------------------------------------------------------
|
| TYPE: bool
| DESCRIPTION: Set TRUE to show a disclaimer message at the bottom of each route.
|
*/
$site['disclaimer'] = TRUE;

/*
| -------------------------------------------------------------------------
| COOKIES WARNING
| -------------------------------------------------------------------------
|
| TYPE: bool
|
| DESCRIPTION:
| Set TRUE to show a dialog warning visitors about cookie usage.
| This script currently does not store any cookie, but third parties like
| Google, CoinGecko or jsDelivr will store functional and analytical cookies.
|
*/
$site['cookies_warning'] = FALSE;
