<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php if ( ! isset( $view ) ) {
    return;
} ?>
<?php
/**
 * Confirmation gate shown right after the paths change.
 *
 * This used to be a separate card with its own Frontend Test and Login Test
 * buttons and its own list of server setup links. Ghost Doctor, which renders
 * directly under this block inside the same card, now runs those checks and can
 * also repair what it finds, so all that is left here is the part Ghost Doctor
 * cannot do for you: showing the new login URL, showing the safe URL that gets
 * you back in if the new one fails, and letting you keep or abort the change.
 */
if ( HMWP_Classes_Tools::getOption( 'test_frontend' ) && HMWP_Classes_Tools::getOption( 'hmwp_mode' ) <> 'default' ) {
    add_action(
            'home_url',
            array(
                    HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' ),
                    'home_url',
            ),
            PHP_INT_MAX,
            1
    );

    if ( defined( 'HMWP_DEFAULT_LOGIN' ) && HMWP_DEFAULT_LOGIN ) {
        $login_url = ( stripos( HMWP_DEFAULT_LOGIN, home_url() ) !== false ) ? HMWP_DEFAULT_LOGIN : home_url( HMWP_DEFAULT_LOGIN );
        $safe_url  = '';
    } else {
        $login_url = site_url() . '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' );
        $safe_url  = site_url() . '/wp-login.php?' . HMWP_Classes_Tools::getOption( 'hmwp_disable_name' );
    }
    ?>
	<?php // No red accent. Nothing has gone wrong here, the paths were changed on
	      // purpose and this block asks you to confirm they work. A danger colour
	      // reads as a failure the moment the page loads. ?>
    <div class="col-sm-12 p-0 m-0 border-bottom">

        <div class="col-sm-12 px-4 py-3">
            <div style="font-weight: 700; font-size: 1.05rem;"><?php echo esc_html__( 'Your paths have changed. Confirm you can still log in.', 'hide-my-wp' ); ?></div>
            <div class="text-muted small mt-1" style="max-width: 62em;">
				<?php echo esc_html__( 'Keep this tab open and do not log out. Open your new login URL in a second tab and check that the login page loads and accepts your password. Run Ghost Doctor below if anything looks wrong, then come back here and keep or undo the change.', 'hide-my-wp' ); ?>
            </div>
        </div>

        <div class="col-sm-12 px-4 pb-3">
            <table class="table table-sm m-0" style="table-layout: fixed;">
                <tr>
                    <td class="border-0 pl-0 align-top text-muted small" style="width: 130px;"><?php echo esc_html__( 'New login URL', 'hide-my-wp' ); ?></td>
                    <td class="border-0 align-top" style="word-break: break-all;">
                        <a href="<?php echo esc_url( $login_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $login_url ); ?></a>
                    </td>
                </tr>
				<?php if ( $safe_url ) { ?>
                    <tr>
                        <td class="border-0 pl-0 align-top text-muted small"><?php echo esc_html__( 'Safe URL', 'hide-my-wp' ); ?></td>
                        <td class="border-0 align-top" style="word-break: break-all;">
                            <a href="<?php echo esc_url( $safe_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $safe_url ); ?></a>
                            <div class="text-muted small"><?php echo esc_html__( 'Use this one if the new login URL does not work. It bypasses the hidden paths and lets you back into the dashboard.', 'hide-my-wp' ); ?></div>
                        </td>
                    </tr>
				<?php } ?>
            </table>
        </div>

        <div class="col-sm-12 px-4 py-3 border-top d-flex flex-row align-items-center">
            <div class="hmwp_confirm">
                <form method="POST" class="m-0">
					<?php wp_nonce_field( 'hmwp_confirm', 'hmwp_nonce' ); ?>
                    <input type="hidden" name="action" value="hmwp_confirm"/>
                    <input type="submit" class="btn rounded-0 btn-success px-4" value="<?php echo esc_attr__( 'Yes, it\'s working', 'hide-my-wp' ); ?>"/>
                </form>
            </div>
            <div class="hmwp_abort ml-2">
                <form method="POST" class="m-0">
					<?php wp_nonce_field( 'hmwp_abort', 'hmwp_nonce' ); ?>
                    <input type="hidden" name="action" value="hmwp_abort"/>
                    <input type="submit" class="btn rounded-0 btn-secondary px-4" value="<?php echo esc_attr__( 'No, abort', 'hide-my-wp' ); ?>"/>
                </form>
            </div>
            <div class="text-muted small ml-3">
				<?php
				echo wp_kses_post(
						sprintf(
						/* translators: 1: Plugin name. 2: Opening anchor tag. 3: Closing anchor tag. */
								__( 'Still stuck? Switch to Deactivated Mode and %2$scontact us%3$s about %1$s.', 'hide-my-wp' ),
								esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ),
								'<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/contact/' ) . '" target="_blank" rel="noopener">',
								'</a>'
						)
				);
				?>
            </div>
        </div>

    </div>
<?php } ?>
