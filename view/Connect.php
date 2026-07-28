<?php defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' ); ?>
<?php if ( ! isset( $view ) ) { return; } ?>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <?php do_action( 'hmwp_notices' ); ?>
        <div class="hmwp_col flex-grow-1 p-0 pr-2 mr-2 mb-3">
            <form method="POST">
                <?php wp_nonce_field( 'hmwp_connect', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_connect"/>
                <input type="hidden" name="howtolessons" value="1"/>

                <?php do_action( 'hmwp_form_notices' ); ?>

                <div id="connect" class="col-sm-12 p-0 m-0">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0" style="line-height: 1.5;">
                            <div class="p-0 m-0" style="font-size: 1.5rem;"><?php echo esc_html__( 'Activate Free WordPress Protection', 'hide-my-wp' ); ?></div>
                            <div class="text-black-50 p-0 m-0" style="font-size: 0.9rem;"><?php echo esc_html__( 'Firewall, login & path security in seconds', 'hide-my-wp' ); ?></div>
                        </h3>
                        <div class="card-body">

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-4 px-3 font-weight-bold">
                                    <?php echo esc_html__( 'Email Address', 'hide-my-wp' ); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__( 'Enter your email to activate', 'hide-my-wp' ); ?></div>
                                </div>
                                <div class="col-sm-7 p-0 input-group ">
                                    <?php
                                    $email = HMWP_Classes_Tools::getOption( 'hmwp_email_address' );
                                    if ( $email == '' ) {
                                        global $current_user;
                                        $email = $current_user->user_email;
                                    }
                                    ?>
                                    <input type="text" class="form-control" name="hmwp_email" value="<?php echo esc_attr( $email ) ?>" placeholder="<?php echo esc_attr( $email ) ?>"/>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer p-0 m-0">
                            <div class="col-sm-12 p-0 m-0">
                                <div class="text-black-50 small p-0 m-0 py-3" style="font-size: 1rem;"><i class="fa fa-check text-success mx-2"></i><?php echo esc_html__( 'Trusted by 100,000+ websites', 'hide-my-wp' ); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-12 my-3 p-0">
                        <button type="submit" class="btn rounded-0 btn-success btn-lg px-5 save" ><?php echo esc_html__( 'Activate Free Protection', 'hide-my-wp' ); ?></button>
                        <span class="text-black-50 p-2 m-0 ml-2"><?php echo esc_html__( 'No spam. Instant activation.', 'hide-my-wp' ); ?></span>
                    </div>
                </div>
            </form>

            <form method="POST">
                <?php wp_nonce_field( 'hmwp_dont_connect', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_dont_connect"/>
                <button type="submit" class="btn rounded-0 float-right btn-link px-3" style="position: relative;margin-top: -60px; color: gray;"><?php echo esc_html__( 'Skip for now', 'hide-my-wp' ); ?></button>
            </form>

        </div>
        <div class="hmwp_col hmwp_col_side p-0 pr-2 mr-2">
            <div class="card col-sm-12 m-0 p-0 rounded-0">
                <div class="card-body f-gray-dark text-left border-bottom">
                    <h3 class="card-title"><?php echo esc_html__( 'Global Stats', 'hide-my-wp' ); ?></h3>
                    <div class="my-4">
                        <ul style="list-style-type: disc; padding-left: 20px;">
                            <li class="py-1"><?php echo esc_html__( '10 Million+ Monthly Brute-Force Attempts Blocked', 'hide-my-wp' ); ?></li>
                            <li class="py-1"><?php echo esc_html__( '100 Million+ Monthly Security Threats Prevented', 'hide-my-wp' ); ?></li>
                        </ul>
                    </div>
                    <h3 class="card-title"><?php echo esc_html__( 'Activation Help', 'hide-my-wp' ); ?></h3>
                    <div class="text-info mt-3">
                        <?php echo sprintf( /* translators: 1: Opening link tag for Terms of Use. 2: Closing link tag. 3: Opening link tag for Privacy Policy. 4: Closing link tag. */ esc_html__( 'By activating, you agree to our %1$s Terms of Use %2$s and %3$sPrivacy Policy%4$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) ) . '/terms-of-use/" target="_blank" rel="noopener" style="font-weight: bold">', '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) ) . '/privacy-policy/" target="_blank" rel="noopener" style="font-weight: bold">', '</a>' ); ?>
                    </div>
                    <div class="text-danger mt-3">
                        <?php
                        echo wp_kses_post( sprintf(
                        /* translators: 1: Plugin name. 2: Opening account link. 3: Closing link. */
                                __( 'If you bought %1$s please remove this plugin and install the one from %2$sYour Account%3$s', 'hide-my-wp' ),
                                HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ),
                                '<a href="' . esc_url( _HMWP_ACCOUNT_SITE_ ) . '" target="_blank" rel="noopener">',
                                '</a>'
                        ) );
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
