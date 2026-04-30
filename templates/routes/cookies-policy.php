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
 * @var array $site
 * @var array $routes
 */

/*
| -------------------------------------------------------------------------
| TITLE
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Route title.
|
*/
$frontend_options['cookies-policy']['title'] = __( 'Cookies Policy' );

/*
| -------------------------------------------------------------------------
| NAME
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Website name
|
*/
$route_cookies_policy['name'] = $site['name'];

/*
| -------------------------------------------------------------------------
| PRIVACY POLICY URL
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Privacy policy URL. Default to "privacy-policy" route URL if enabled.
|
*/
$route_cookies_policy['privacy_policy_url'] = empty( $routes['privacy-policy']['enabled'] ) ? '' : site_url( $routes['privacy-policy']['path'] );

/*
| -------------------------------------------------------------------------
| COOKIE DEFINITION
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "What Are Cookies?" section.
|
*/
$route_cookies_policy['cookie_definition'] = TRUE;

/*
| -------------------------------------------------------------------------
| USES
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "What Are Cookies Used For?" section.
|
*/
$route_cookies_policy['uses'] = TRUE;

/*
| -------------------------------------------------------------------------
| TYPES
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "What Type Of Do We Use?" section.
|
*/
$route_cookies_policy['types'] = TRUE;

/*
| -------------------------------------------------------------------------
| TYPE STRICTLY NECESSARY
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Strictly necessary cookies" definition.
|
*/
$route_cookies_policy['type_strictly_necessary'] = TRUE;

/*
| -------------------------------------------------------------------------
| TYPE FUNCTIONAL
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Functional cookies" definition.
|
*/
$route_cookies_policy['type_functional'] = TRUE;

/*
| -------------------------------------------------------------------------
| TYPE ADVERTISING
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Advertising cookies" definition.
|
*/
$route_cookies_policy['type_advertising'] = TRUE;

/*
| -------------------------------------------------------------------------
| TYPE ANALYTICAL
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Analytical cookies" definition.
|
*/
$route_cookies_policy['type_analytical'] = TRUE;

/*
| -------------------------------------------------------------------------
| MANAGE
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "How Can You Manage Your Cookies?" section.
|
*/
$route_cookies_policy['manage'] = TRUE;

?>
<v-container tag="section" id="cookies-policy" class="mt-8 mb-16 pa-4 pa-sm-6">
    <h1 class="text-h4 text-sm-h3 mb-8">
        <?php echo esc_html( $frontend_options['cookies-policy']['title'] ); ?>
    </h1>

    <?php if ( ! empty( $route_cookies_policy['privacy_policy_url'] ) ) : ?>
        <p>
            <?php echo esc_html( "This policy should be analyzed complementarily and jointly with our" ); ?>
            <a href="<?php echo esc_url( $route_cookies_policy['privacy_policy_url'] ); ?>"><?php echo esc_html( "Privacy Policy" ); ?></a>.
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $route_cookies_policy['cookie_definition'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "What Are Cookies?" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "Cookies are small information files that are stored on your electronic device and that can take on various functions, being able to store the user’s preferences regarding certain types of information or store information related to their website browsing experience, its main objective being the detection of the user in their visits to the website and respective configuration parameters." ); ?>
                <br>
                <?php echo esc_html( "The use of cookies on the Internet is normal and in no way harms users’ web access computers and other electronic devices." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_cookies_policy['uses'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "What Are Cookies Used For?" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "Cookies have various functions, such as helping a website’s content management and updating area to understand more deeply how it is used, facilitating their browsing, saving user preferences and, generally speaking, improving their user experience and ensuring that the websites present relevant content to each user." ); ?>
                <?php echo esc_html( "Thus, they are instruments of continuous improvement in the browsing experience." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_cookies_policy['types'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "What Type Of Do We Use?" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "There are several types of cookies which act in different ways and with different purposes regarding your web browsing experience." ); ?>
                <?php echo esc_html( "We present the existing categories we use:" ); ?>
            </p>
            <ul>
                <?php if ( ! empty( $route_cookies_policy['type_strictly_necessary'] ) ) : ?>
                <li>
                    <strong><?php echo esc_html( "Strictly necessary cookies:" ); ?></strong>
                    <?php echo esc_html( "these essential cookies guarantee website navigation and use of its functionalities, including registration of whether or not the user has authorised the use of cookies on the website." ); ?>
                    <?php echo esc_html( "These cookies cannot be rejected when using a website." ); ?>
                </li>
                <?php endif; ?>

                <?php if ( ! empty( $route_cookies_policy['type_functional'] ) ) : ?>
                <li>
                    <strong><?php echo esc_html( "Functional cookies:" ); ?></strong>
                    <?php echo esc_html( "these cookies allow websites to remember information about user choices, such as username, language, region from which they access and user customization of the website browsing experience." ); ?>
                    <?php echo esc_html( "These cookies lose their validity at the end of the user’s session (for example, when the user closes the browser window) or may remain for a longer period to store the user’s website preferences and choices." ); ?>
                </li>
                <?php endif; ?>

                <?php if ( ! empty( $route_cookies_policy['type_advertising'] ) ) : ?>
                <li>
                    <strong><?php echo esc_html( "Advertising cookies:" ); ?></strong>
                    <?php echo esc_html( "these cookies collect information on the user’s browsing habits by registering website uses." ); ?>
                    <?php echo esc_html( "These cookies are used to support the performance of more targeted marketing and communication actions, allowing the distinction and identification of individual usage preferences or storage of a code that translates a set of usage habits or preferences." ); ?>
                </li>
                <?php endif; ?>

                <?php if ( ! empty( $route_cookies_policy['type_analytical'] ) ) : ?>
                <li>
                    <strong><?php echo esc_html( "Analytical cookies:" ); ?></strong>
                    <?php echo esc_html( "these cookies are used to analyse how users use the website and to monitor the website’s performance, for example, which pages have the highest volume of visits or if there are any error message returned to the user." ); ?>
                    <?php echo esc_html( "This makes it possible to provide a high-quality experience by customizing the offering and quickly identifying and fixing any issues that may arise." ); ?>
                    <?php echo esc_html( "These cookies are used for continuous improvement and statistical analysis." ); ?>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_cookies_policy['manage'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "How Can You Manage Your Cookies?" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "Different browsers offer different ways to manage cookies implemented by the websites you visit." ); ?>
                <?php echo esc_html( "There is no standard way to remove cookies and, depending on the browser you use, there are several advanced ways to manage them." ); ?>
                <?php echo esc_html( "However, all browsers allow a general removal of cookies from your computer." ); ?>
                <br>
                <?php echo esc_html( "We remind you that disabling cookies determines that all or some of the available features may be inhibited, penalizing your browsing experience." ); ?>
                <br>
                <?php echo esc_html( "To manage the use of cookies in your browser, proceed accordingly:" ); ?>
            </p>

            <strong><?php echo esc_html( "Google Chrome" ); ?></strong>
            <ul>
                <li><?php echo esc_html( "Select the menu \"Settings\";" ); ?></li>
                <li><?php echo esc_html( "Select the option \"Show advanced settings\";" ); ?></li>
                <li><?php echo esc_html( "Under \"Privacy\", select \"Content Settings\";" ); ?></li>
            </ul>
            <p>
                <?php echo esc_html( "More information" ); ?> <a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener"><?php echo esc_html( "here" ); ?></a>.
            </p>

            <strong><?php echo esc_html( "Firefox" ); ?></strong>
            <ul>
                <li><?php echo esc_html( "Select the menu \"Tools\";" ); ?></li>
                <li><?php echo esc_html( "Select the option \"Preferences\";" ); ?></li>
                <li><?php echo esc_html( "On top, select the icon \"Privacy\";" ); ?></li>
            </ul>
            <p>
                <?php echo esc_html( "More information" ); ?> <a href="https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox" target="_blank" rel="noopener"><?php echo esc_html( "here" ); ?></a>.
            </p>

            <strong><?php echo esc_html( "Internet Explorer" ); ?></strong>
            <ul>
                <li><?php echo esc_html( "Select the menu \"Tools\";" ); ?></li>
                <li><?php echo esc_html( "Select the option \"InternetOptions\";" ); ?></li>
                <li><?php echo esc_html( "Select the tab \"Privacy\";" ); ?></li>
            </ul>
            <p>
                <?php echo esc_html( "More information" ); ?> <a href="https://support.microsoft.com/en-us/windows/delete-and-manage-cookies-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener"><?php echo esc_html( "here" ); ?></a>.
            </p>

            <strong><?php echo esc_html( "Safari" ); ?></strong>
            <ul>
                <li><?php echo esc_html( "Select the menu \"Edit\";" ); ?></li>
                <li><?php echo esc_html( "Select the option \"Preferences\";" ); ?></li>
                <li><?php echo esc_html( "On top, select the icon \"Privacy\";" ); ?></li>
            </ul>
            <p><?php echo esc_html( "More information" ); ?> <a href="https://support.apple.com/en-us/HT201265" target="_blank" rel="noopener"><?php echo esc_html( "here" ); ?></a>.</p>

            <p>
                <?php echo esc_html( "If you use more than one browser, you should manage your cookies preferences in each one separately." ); ?>
            </p>
            <p>
                <?php echo esc_html( "More information on Cookies at:" ); ?> <a href="https://www.allaboutcookies.org/" target="_blank" rel="noopener">allaboutcookies.org</a>
            </p>
        </div>
    <?php endif; ?>

</v-container>
