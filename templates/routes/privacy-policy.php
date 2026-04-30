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
$frontend_options['privacy-policy']['title'] = __( 'Privacy Policy' );

/*
| -------------------------------------------------------------------------
| NAME
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Website name
|
*/
$route_privacy_policy['name'] = $site['name'];

/*
| -------------------------------------------------------------------------
| HOST
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Website hostname
|
*/
$route_privacy_policy['host'] = parse_url( site_url(), PHP_URL_HOST );

/*
| -------------------------------------------------------------------------
| INFO COLLECTED
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Information We Collect" section
|
*/
$route_privacy_policy['info_collected'] = TRUE;

/*
| -------------------------------------------------------------------------
| HOW INFO USED
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "How We Use Your Information" section
|
*/
$route_privacy_policy['how_info_used'] = TRUE;

/*
| -------------------------------------------------------------------------
| LOG FILES
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Log Files" section
|
*/
$route_privacy_policy['log_files'] = TRUE;

/*
| -------------------------------------------------------------------------
| COOKIES AND LOCAL STORAGE
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Cookies And Local Storage" section
|
*/
$route_privacy_policy['cookies_local_storage'] = TRUE;

/*
| -------------------------------------------------------------------------
| COOKIES POLICY URL
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Cookies policy URL. Default to "cookies-policy" route URL if enabled.
|
*/
$route_privacy_policy['cookies_policy_url'] = empty( $routes['cookies-policy']['enabled'] ) ? '' : site_url( $routes['cookies-policy']['path'] );

/*
| -------------------------------------------------------------------------
| PARTNERS
| -------------------------------------------------------------------------
| TYPE: array
| DESCRIPTION: List of partners. Leave empty to disable "Our Advertising Partners" sections.
| KEY: Partner name.
| VALUE: Policy page URL.
|
| -------------------------------------------------------------------------
| EXAMPLE
| -------------------------------------------------------------------------
|
|  $route_privacy_policy['partners'] = [
|      'Google' => 'https://policies.google.com/technologies/ads',
|  ];
|
*/
$route_privacy_policy['partners'] = [

];

/*
| -------------------------------------------------------------------------
| PARTNERS PRIVACY
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Advertising Partners Privacy Policies" section.
|
*/
$route_privacy_policy['partners_privacy'] = TRUE;

/*
| -------------------------------------------------------------------------
| THIRD PARTY
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Third Party Privacy Policies" section.
|
*/
$route_privacy_policy['third_party'] = TRUE;

/*
| -------------------------------------------------------------------------
| CCPA
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "CCPA Privacy Rights (Do Not Sell My Personal Information)" section.
|
*/
$route_privacy_policy['ccpa'] = TRUE;

/*
| -------------------------------------------------------------------------
| GDPR
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "GDPR Privacy Policy (Data Protection Rights)" section.
|
*/
$route_privacy_policy['gdpr'] = TRUE;

/*
| -------------------------------------------------------------------------
| CHILDREN
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show "Children's Information" section.
|
*/
$route_privacy_policy['children'] = TRUE;

?>
<v-container tag="section" id="privacy-policy" class="mt-8 mb-16 pa-4 pa-sm-6">
    <h1 class="text-h4 text-sm-h3 mb-8">
        <?php echo esc_html( $frontend_options['privacy-policy']['title'] ); ?>
    </h1>
    <p>
        <?php echo esc_html( "At {$route_privacy_policy['name']}, accessible from {$route_privacy_policy['host']}, one of our main priorities is the privacy of our visitors." ); ?>
        <?php echo esc_html( "This Privacy Policy document contains types of information that is collected and recorded by {$route_privacy_policy['name']} and how we use it." ); ?>
    </p>
    <p>
        <?php echo esc_html( "If you have additional questions or require more information about our Privacy Policy, do not hesitate to contact us." ); ?>
    </p>
    <p>
        <?php echo esc_html( "This Privacy Policy applies only to our online activities and is valid for visitors to our website with regards to the information that they shared and/or collect in {$route_privacy_policy['name']}." ); ?>
        <?php echo esc_html( "This policy is not applicable to any information collected offline or via channels other than this website." ); ?>
    </p>

    <div class="mt-12">
        <h3 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( "Consent" ); ?>
        </h3>
        <p>
            <?php echo esc_html( "By using our website, you hereby consent to our Privacy Policy and agree to its terms." ); ?>
        </p>
    </div>

    <?php if ( ! empty( $route_privacy_policy['info_collected'] ) ) : ?>
    <div class="mt-12">
        <h3 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( "Information We Collect" ); ?>
        </h3>
        <p>
            <?php echo esc_html( "The personal information that you are asked to provide, and the reasons why you are asked to provide it, will be made clear to you at the point we ask you to provide your personal information." ); ?>
        </p>
        <p>
            <?php echo esc_html( "If you contact us directly, we may receive additional information about you such as your name, email address, phone number, the contents of the message and/or attachments you may send us, and any other information you may choose to provide." ); ?>
        </p>
        <p>
            <?php echo esc_html( "When you register for an Account, we may ask for your contact information, including items such as name, company name, address, email address, and telephone number." ); ?>
        </p>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_privacy_policy['how_info_used'] ) ) : ?>
    <div class="mt-12">
        <h3 class="text-h6 text-sm-h5 mb-4">
            <?php echo esc_html( "How We Use Your Information" ); ?>
        </h3>
        <p>
            <?php echo esc_html( "We use the information we collect in various ways, including to:" ); ?>
        </p>
        <ul>
            <li>
                <?php echo esc_html( "Provide, operate, and maintain our website" ); ?>
            </li>
            <li>
                <?php echo esc_html( "Improve, personalize, and expand our website" ); ?>
            </li>
            <li>
                <?php echo esc_html( "Understand and analyze how you use our website" ); ?>
            </li>
            <li>
                <?php echo esc_html( "Develop new products, services, features, and functionality" ); ?>
            </li>
            <li>
                <?php echo esc_html( "Communicate with you, either directly or through one of our partners, including for customer service, to provide you with updates and other information relating to the website, and for marketing and promotional purposes" ); ?>
            </li>
            <li>
                <?php echo esc_html( "Send you emails" ); ?>
            </li>
            <li>
                <?php echo esc_html( "Find and prevent fraud" ); ?>
            </li>
        </ul>
    </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_privacy_policy['log_files'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "Log Files" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "{$route_privacy_policy['name']} follows a standard procedure of using log files." ); ?>
                <?php echo esc_html( "These files log visitors when they visit websites." ); ?>
                <?php echo esc_html( "All hosting companies do this and a part of hosting services' analytics." ); ?>
                <?php echo esc_html( "The information collected by log files include internet protocol (IP) addresses, browser type, Internet Service Provider (ISP), date and time stamp, referring/exit pages, and possibly the number of clicks." ); ?>
                <?php echo esc_html( "These are not linked to any information that is personally identifiable." ); ?>
                <?php echo esc_html( "The purpose of the information is for analyzing trends, administering the site, tracking users' movement on the website, and gathering demographic information." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_privacy_policy['cookies_local_storage'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "Cookies And Local Storage" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "Like any other website, {$route_privacy_policy['name']} uses 'cookies'." ); ?>
                <?php echo esc_html( "These cookies are used to store information including visitors' preferences, and the pages on the website that the visitor accessed or visited." ); ?>
                <?php echo esc_html( "The information is used to optimize the users' experience by customizing our web page content based on visitors' browser type and/or other information." ); ?>
            </p>
            <?php if ( ! empty( $route_privacy_policy['cookies_policy_url'] ) ) : ?>
                <p>
                    <?php echo esc_html( "Read our " ); ?>
                    <a href="<?php echo esc_url( $route_privacy_policy['cookies_policy_url'] ); ?>"><?php echo esc_html( "Cookies Policy" ); ?></a>
                    <?php echo esc_html( " for better understanding about cookie usage." ); ?>
                </p>
            <?php endif; ?>
            <p>
                <?php echo esc_html( "{$route_privacy_policy['name']} uses 'Local Storage' to store your preferences. Those preferences include theme, price currency and any other functional information required to run the website." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_privacy_policy['partners'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "Our Advertising Partners" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "Some of advertisers on our site may use cookies and web beacons." ); ?>
                <?php echo esc_html( "Our advertising partners are listed below." ); ?>
                <?php echo esc_html( "Each of our advertising partners has their own Privacy Policy for their policies on user data." ); ?>
                <?php echo esc_html( "For easier access, we hyperlinked to their Privacy Policies below." ); ?>
            </p>
            <ul>
                <?php
                    foreach ( $route_privacy_policy['partners'] as $partner => $link ) {
                        ?>
                        <li>
                            <?php echo esc_html( $partner ); ?>
                            –
                            <a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $link ); ?></a>
                        </li>
                        <?php
                    }
                ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_privacy_policy['partners_privacy'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "Advertising Partners Privacy Policies" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "You may consult this list to find the Privacy Policy for each of the advertising partners of {$route_privacy_policy['name']}." ); ?>
                <?php echo esc_html( "Third-party ad servers or ad networks uses technologies like cookies, JavaScript, or Web Beacons that are used in their respective advertisements and links that appear on {$route_privacy_policy['name']}, which are sent directly to users' browser." ); ?>
                <?php echo esc_html( "They automatically receive your IP address when this occurs." ); ?>
                <?php echo esc_html( "These technologies are used to measure the effectiveness of their advertising campaigns and/or to personalize the advertising content that you see on websites that you visit." ); ?>
            </p>
            <p>
                <?php echo esc_html( "Note that {$route_privacy_policy['name']} has no access to or control over these cookies that are used by third-party advertisers." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_privacy_policy['third_party'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "Third Party Privacy Policies" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "{$route_privacy_policy['name']}'s Privacy Policy does not apply to other advertisers or websites." ); ?>
                <?php echo esc_html( "Thus, we are advising you to consult the respective Privacy Policies of these third-party ad servers for more detailed information." ); ?>
                <?php echo esc_html( "It may include their practices and instructions about how to opt-out of certain options." ); ?>
                <?php echo esc_html( "These technologies on websites that you visit." ); ?>
            </p>
            <p>
                <?php echo esc_html( "You can choose to disable cookies through your individual browser options." ); ?>
                <?php echo esc_html( "To know more detailed information about cookie management with specific web browsers, it can be found at the browsers' respective websites." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_privacy_policy['ccpa'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "CCPA Privacy Rights (Do Not Sell My Personal Information)" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "Under the CCPA, among other rights, California consumers have the right to:" ); ?>
            </p>
            <ul>
                <li>
                    <?php echo esc_html( "Request that a business that collects a consumer's personal data disclose the categories and specific pieces of personal data that a business has collected about consumers." ); ?>
                </li>
                <li>
                    <?php echo esc_html( "Request that a business delete any personal data about the consumer that a business has collected." ); ?>
                </li>
                <li>
                    <?php echo esc_html( "Request that a business that sells a consumer's personal data, not sell the consumer's personal data." ); ?>
                </li>
            </ul>
            <br>
            <p>
                <?php echo esc_html( "If you make a request, we have one month to respond to you. If you would like to exercise any of these rights, please contact us." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_privacy_policy['gdpr'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "GDPR Privacy Policy (Data Protection Rights)" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "We would like to make sure you are fully aware of all of your data protection rights. Every user is entitled to the following:" ); ?>
            </p>
            <ul>
                <li>
                    <?php echo esc_html( "The right to access – You have the right to request copies of your personal data. We may charge you a small fee for this service." ); ?>
                </li>
                <li>
                    <?php echo esc_html( "The right to rectification – You have the right to request that we correct any information you believe is inaccurate." ); ?>
                    <?php echo esc_html( "You also have the right to request that we complete the information you believe is incomplete." ); ?>
                </li>
                <li>
                    <?php echo esc_html( "The right to erasure – You have the right to request that we erase your personal data, under certain conditions." ); ?>
                </li>
                <li>
                    <?php echo esc_html( "The right to restrict processing – You have the right to request that we restrict the processing of your personal data, under certain conditions." ); ?>
                </li>
                <li>
                    <?php echo esc_html( "The right to object to processing – You have the right to object to our processing of your personal data, under certain conditions." ); ?>
                </li>
                <li>
                    <?php echo esc_html( "The right to data portability – You have the right to request that we transfer the data that we have collected to another organization, or directly to you, under certain conditions." ); ?>
                </li>
            </ul>
            <br>
            <p>
                <?php echo esc_html( "If you make a request, we have one month to respond to you. If you would like to exercise any of these rights, please contact us." ); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $route_privacy_policy['children'] ) ) : ?>
        <div class="mt-12">
            <h3 class="text-h6 text-sm-h5 mb-4">
                <?php echo esc_html( "Children's Information" ); ?>
            </h3>
            <p>
                <?php echo esc_html( "Another part of our priority is adding protection for children while using the internet." ); ?>
                <?php echo esc_html( "We encourage parents and guardians to observe, participate in, and/or monitor and guide their online activity." ); ?>
            </p>
            <p>
                <?php echo esc_html( "{$route_privacy_policy['name']} does not knowingly collect any Personal Identifiable Information from children under the age of 13." ); ?>
                <?php echo esc_html( "If you think that your child provided this kind of information on our website, we strongly encourage you to contact us immediately and we will do our best efforts to promptly remove such information from our records." ); ?>
            </p>
        </div>
    <?php endif; ?>

</v-container>
