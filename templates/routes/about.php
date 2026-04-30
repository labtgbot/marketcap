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
 */

/*
| -------------------------------------------------------------------------
| TITLE
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Route title.
|
*/
$frontend_options['about']['title'] = sprintf( '%s %s', __( 'About' ), $site['name'] );

/*
| -------------------------------------------------------------------------
| NAME
| -------------------------------------------------------------------------
| TYPE: string
| DESCRIPTION: Website name.
| DEFAULT: $site['name']
|
*/
$route_about['name'] = $site['name'];

/*
| -------------------------------------------------------------------------
| TEAM
| -------------------------------------------------------------------------
| TYPE: bool
| DESCRIPTION: Set TRUE to show team section.
|
*/
$route_about['team'] = TRUE;

/*
| -------------------------------------------------------------------------
| TEAM MEMBERS
| -------------------------------------------------------------------------
|
| TYPE: array
| DESCRIPTION: Team members details.
|
| -------------------------------------------------------------------------
| EXPLANATION OF MEMBER PARAMETERS
| -------------------------------------------------------------------------
|
|   ['name']        (string)    Name
|   ['avatar']      (string)    Avatar image absolute or relative URL
|   ['role']        (string)    Role in the website/company
|   ['facebook']    (string)    Facebook page URL
|   ['twitter']     (string)    Twitter page URL
|   ['linkedin']    (string)    LinkedIn page URL
|
*/
$route_about['team_members'] = [
    [
        'name'      => 'Nikolas Berry',
        'avatar'    => 'assets/images/team/nikolas-berry.jpg',
        'role'      => 'CEO',
        'facebook'  => '#',
        'twitter'   => '#',
        'linkedin'  => '#',
    ],
    [
        'name'      => 'Vincent Adams',
        'avatar'    => 'assets/images/team/vincent-adams.jpg',
        'role'      => 'COO',
        'facebook'  => '#',
        'twitter'   => '#',
        'linkedin'  => '#',
    ],
    [
        'name'      => 'Issac Nicholson',
        'avatar'    => 'assets/images/team/issac-nicholson.jpg',
        'role'      => 'UI/UX Designer',
        'facebook'  => '#',
        'twitter'   => '#',
        'linkedin'  => '#',
    ],
    [
        'name'      => 'Paige Carson',
        'avatar'    => 'assets/images/team/paige-carson.jpg',
        'role'      => 'UI/UX Designer',
        'facebook'  => '#',
        'twitter'   => '#',
        'linkedin'  => '#',
    ],
        [
        'name'      => 'Matteo Enriquez',
        'avatar'    => 'assets/images/team/matteo-enriquez.jpg',
        'role'      => 'Software Engineer',
        'facebook'  => '#',
        'twitter'   => '#',
        'linkedin'  => '#',
    ],
    [
        'name'      => 'Yousif Sharma',
        'avatar'    => 'assets/images/team/yousif-sharma.jpg',
        'role'      => 'Data Analyst',
        'facebook'  => '#',
        'twitter'   => '#',
        'linkedin'  => '#',
    ],
    [
        'name'      => 'Serena Frost',
        'avatar'    => 'assets/images/team/serena-frost.jpg',
        'role'      => 'Marketing',
        'facebook'  => '#',
        'twitter'   => '#',
        'linkedin'  => '#',
    ],
    [
        'name'      => 'Melisa Yu',
        'avatar'    => 'assets/images/team/melisa-yu.jpg',
        'role'      => 'Public Relations',
        'facebook'  => '#',
        'twitter'   => '#',
        'linkedin'  => '#',
    ],
];

?>
<v-container tag="section" id="about" class="mt-8 mb-16 pa-4 pa-sm-6">

    <div id="about-intro">
        <h2 class="text-h5 text-sm-h4 mb-5 text-center">
            <?php echo esc_html( $frontend_options['about']['title'] ); ?>
        </h2>
        <p>
            <?php echo esc_html( "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Enim nunc faucibus a pellentesque sit. Eget nunc lobortis mattis aliquam faucibus purus in. Nunc pulvinar sapien et ligula. Urna molestie at elementum eu facilisis sed odio morbi. At ultrices mi tempus imperdiet nulla. Commodo nulla facilisi nullam vehicula ipsum. Elementum pulvinar etiam non quam. Vulputate mi sit amet mauris." ); ?>
        </p>
        <p>
            <?php echo esc_html( "Vitae tortor condimentum lacinia quis vel eros donec ac odio. Vel quam elementum pulvinar etiam non quam. Auctor elit sed vulputate mi sit. Auctor urna nunc id cursus metus aliquam eleifend mi. Tincidunt vitae semper quis lectus nulla at volutpat. Arcu risus quis varius quam quisque id diam vel. Non arcu risus quis varius quam. Lorem dolor sed viverra ipsum. Mi ipsum faucibus vitae aliquet. Ipsum dolor sit amet consectetur adipiscing." ); ?>
        </p>
    </div>

    <?php if ( ! empty( $route_about['team'] ) ) : ?>

        <div id="about-team" class="mt-16">
            <h2 class="text-h5 text-sm-h4 mb-6 text-center">
                <?php echo esc_html( "Our Team" ); ?>
            </h2>

            <p class="text-center">
                <?php echo esc_html( "Meet the team behind the development of this project!" ); ?>
                <br>
                <?php echo esc_html( "We appreciate the talent and skills of each element, but above all is the great attachment to the crypto community what make us different." ); ?>
            </p>

            <?php if ( ! empty( $route_about['team_members'] ) ) : ?>
                <v-row class="mt-8">
                    <?php foreach ( $route_about['team_members'] as $member ) : ?>
                        <v-col cols="6" sm="4" md="3" class="text-center">
                            <div>
                                <v-avatar :size="128">
                                    <v-img src="<?php echo esc_url( get_file_url_for_display( $member['avatar'] ) ); ?>"></v-img>
                                </v-avatar>
                            </div>
                            <h4 class="mt-2"><?php echo esc_html( $member['name'] ); ?></h4>
                            <div class="text-uppercase caption"><?php echo esc_html( $member['role'] ); ?></div>
                            <div>
                                <?php if ( ! empty( $member['facebook'] ) ) : ?>
                                    <v-btn icon href="<?php echo esc_url( $member['facebook'] ); ?>" target="_blank" rel="noopener">
                                        <v-icon>mdi-facebook</v-icon>
                                    </v-btn>
                                <?php endif; ?>

                                <?php if ( ! empty( $member['twitter'] ) ) : ?>
                                    <v-btn icon href="<?php echo esc_url( $member['twitter'] ); ?>" target="_blank" rel="noopener">
                                        <v-icon>mdi-twitter</v-icon>
                                    </v-btn>
                                <?php endif; ?>

                                <?php if ( ! empty( $member['linkedin'] ) ) : ?>
                                    <v-btn icon href="<?php echo esc_url( $member['linkedin'] ); ?>" target="_blank" rel="noopener">
                                        <v-icon>mdi-linkedin</v-icon>
                                    </v-btn>
                                <?php endif; ?>
                            </div>
                        </v-col>
                    <?php endforeach; ?>
                </v-row>
            <?php endif; ?>

        </div>
    <?php endif; ?>

</v-container>