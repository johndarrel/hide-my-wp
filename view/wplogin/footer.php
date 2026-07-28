<?php

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

/**
 * Outputs the footer for the login page.
 *
 * @since 3.1.0
 *
 * @global bool|string $interim_login Whether interim login modal is being displayed. String 'success'
 *                                    upon successful login.
 *
 * @param string $input_id Which input to auto-focus.
 */
function login_footer( $input_id = '' ) {
    global $interim_login;

    // Don't allow interim logins to navigate away from the page.
    if ( ! $interim_login ) {
        ?>
        <p id="backtoblog">
            <?php
            $html_link = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( home_url( '/' ) ),
                    sprintf(
                    /* translators: %s: Site title. */
                            _x( '&larr; Go to %s', 'site' ), //phpcs:ignore WordPress.WP.I18n.MissingArgDomain
                            get_bloginfo( 'title', 'display' )
                    )
            );
            /**
             * Filters the "Go to site" link displayed in the login page footer.
             *
             * @since 5.7.0
             *
             * @param string $link HTML link to the home URL of the current site.
             */
            echo apply_filters( 'login_site_html_link', $html_link ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </p>
        <?php

        the_privacy_policy_link( '<div class="privacy-policy-page-link">', '</div>' );
    }

    ?>
    </div><?php // End of <div id="login">. ?>

    <?php
    if (
            ! $interim_login &&
            /**
             * Filters whether to display the Language selector on the login screen.
             *
             * @since 5.9.0
             *
             * @param bool $display Whether to display the Language selector on the login screen.
             */
            apply_filters( 'login_display_language_dropdown', true )
    ) {
        $languages = get_available_languages();

        if ( ! empty( $languages ) ) {
            ?>
            <div class="language-switcher">
                <form id="language-switcher" method="get">

                    <label for="language-switcher-locales">
                        <span class="dashicons dashicons-translation" aria-hidden="true"></span>
                        <span class="screen-reader-text">
							<?php
                            /* translators: Hidden accessibility text. */
                            _e( 'Language' ); //phpcs:ignore
                            ?>
						</span>
                    </label>

                    <?php
                    $args = array(
                            'id'                          => 'language-switcher-locales',
                            'name'                        => 'wp_lang',
                            'selected'                    => determine_locale(),
                            'show_available_translations' => false,
                            'explicit_option_en_us'       => true,
                            'languages'                   => $languages,
                    );

                    /**
                     * Filters default arguments for the Languages select input on the login screen.
                     *
                     * The arguments get passed to the wp_dropdown_languages() function.
                     *
                     * @since 5.9.0
                     *
                     * @param array $args Arguments for the Languages select input on the login screen.
                     */
                    wp_dropdown_languages( apply_filters( 'login_language_dropdown_args', $args ) );
                    ?>

                    <?php if ( $interim_login ) { ?>
                        <input type="hidden" name="interim-login" value="1" />
                    <?php } ?>

                    <?php if ( isset( $_GET['redirect_to'] ) && '' !== $_GET['redirect_to'] ) {  //phpcs:ignore ?>
                        <input type="hidden" name="redirect_to" value="<?php echo sanitize_url( $_GET['redirect_to'] ); //phpcs:ignore?>" />
                    <?php } ?>

                    <?php if ( isset( $_GET['action'] ) && '' !== $_GET['action'] ) {  //phpcs:ignore ?>
                        <input type="hidden" name="action" value="<?php echo esc_attr( $_GET['action'] );  //phpcs:ignore ?>" />
                    <?php } ?>

                    <input type="submit" class="button" value="<?php esc_attr_e( 'Change' ); //phpcs:ignore WordPress.WP.I18n.MissingArgDomain ?>">

                </form>
            </div>
        <?php } ?>
    <?php } ?>

    <?php

    if ( ! empty( $input_id ) ) {
        ob_start();
        ?>
        <script>
            try{document.getElementById('<?php echo $input_id; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>').focus();}catch(e){}
            if(typeof wpOnload==='function')wpOnload();
        </script>
        <?php
        wp_print_inline_script_tag( wp_remove_surrounding_empty_script_tags( ob_get_clean() ) );
    }

    /**
     * Fires in the login page footer.
     *
     * @since 3.1.0
     */
    do_action( 'login_footer' );

    ?>
    </body>
    </html>
    <?php
}

/**
 * Outputs the JavaScript to handle the form shaking on the login page.
 *
 * @since 1.0.0
 */
function wp_shake_js() {
	?>
	<script type="text/javascript">
	document.querySelector('form').classList.add('shake');
	</script>
	<?php
}
