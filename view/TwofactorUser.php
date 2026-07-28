<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) {
    return;
}
?>

<table class="form-table" id="hmwp_totp_wrap" >
    <tr>
        <th>
            <hr style="margin: 10px 0"/>
            <div class="hmwp_title" style="font-size: medium"><?php echo esc_html__( '2FA Setup', 'hide-my-wp' ); ?></div>
            <div class="hmwp_description" style="margin-top: 5px; font-size: x-small; color: #aaaaaa"><?php echo esc_html( _HMWP_PLUGIN_FULL_NAME_ ); ?> </div>
        </th>
    </tr>
    <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_2fa_user' ) && isset( $view->user ) ) : ?>
        <tr>
            <td style="padding: 10px 0; margin: 0">
                <table>
                    <th>
                        <?php echo esc_html__( 'Two-Factor Method', 'hide-my-wp' ); ?>
                    </th>
                    <td>
                        <input type="hidden" name="hmwp_2fa_method_nonce" value="<?php echo esc_attr( wp_create_nonce( 'hmwp_2fa_method' ) ) ?>"/>
                        <input type="hidden" name="hmwp_2fa_method_action" value="hmwp_2fa_method"/>
                        <input type="hidden" name="hmwp_2fa_method_referer" value="<?php echo esc_url( remove_query_arg( '_wp_http_referer' ) ); ?>"/>
                        <input type="hidden" name="hmwp_2fa_method_user_id" value="<?php echo esc_attr( $view->user->ID ); ?>"/>

                        <select id="hmwp_2fa_method" name="hmwp_2fa_method">

                            <option value="hmwp_2fa_totp" <?php selected( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor' )->isActiveService( $view->user, 'hmwp_2fa_totp' ) ) ?> >
                                <?php echo esc_html__( '2FA Code (Authenticator App)', 'hide-my-wp' ); ?>
                            </option>

                            <option value="hmwp_2fa_email"  <?php selected( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor' )->isActiveService( $view->user, 'hmwp_2fa_email' ) ) ?>>
                                <?php echo esc_html__( 'Email Code', 'hide-my-wp' ); ?>
                            </option>

                            <option value="hmwp_2fa_passkey" <?php selected( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Twofactor' )->isActiveService( $view->user, 'hmwp_2fa_passkey' ) ) ?>>
                                <?php echo esc_html__( 'Passkey (Face ID / Touch ID / Windows Hello)', 'hide-my-wp' ); ?>
                            </option>

                        </select>

                    </td>
                </table>

            </td>
        </tr>
    <?php endif; ?>
    <tr>
        <td id="hmwp_totp_options" style="padding: 10px 0; margin: 0">
            <?php do_action( 'hmwp_two_factor_user_options' ); ?>
        </td>
    </tr>
</table>