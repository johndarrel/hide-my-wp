<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) return;
?>

<table>
    <tr>
        <th>
            <?php echo esc_html__( 'Code Setup', 'hide-my-wp' ); ?>
        </th>
        <td>
            <?php if ( ! empty( $view->options ) ) { ?>
                <?php if ( isset( $view->options['url'] ) && $view->options['url'] <> '' ) { ?>
                    <table>
                        <td>
                            <div id="hmwp_qr_code">
                                <a href="<?php echo esc_attr( $view->options['url'] ); ?>">
                                    <?php echo esc_html__( 'Loading...', 'hide-my-wp' ); ?>
                                    <img src="<?php echo esc_url( _HMWP_WPLOGIN_URL_ . 'images/loading.gif' ); ?>" alt="" />
                                </a>
                            </div>
                        </td>
                        <td>
                            <ol>
                                <li>
                                    <p><?php esc_html_e( 'Download and start the application of your choice.', 'hide-my-wp' ); ?></p>
                                    <p class="hmwp_description"><?php esc_html_e( 'Click on the icon of the app that you are using for a detailed guide on how to set it up.', 'hide-my-wp' ); ?> </p>
                                    <div class="hmwp_apps_wrapper">
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/setting-up-two-factor-authentication-2fa-for-wordpress-using-mobile-apps/#ghost-google-authenticator' ); ?>" target="_blank" class="hmwp_app_logo"><img src="<?php echo esc_url( _HMWP_ASSETS_URL_ . 'img/google-logo.png' ); ?>" alt=""></a>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/setting-up-two-factor-authentication-2fa-for-wordpress-using-mobile-apps/#ghost-authy' ); ?>" target="_blank" class="hmwp_app_logo"><img src="<?php echo esc_url( _HMWP_ASSETS_URL_ . 'img/authy-logo.png' ); ?>" alt=""></a>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/setting-up-two-factor-authentication-2fa-for-wordpress-using-mobile-apps/#ghost-microsoft-authenticator' ); ?>" target="_blank" class="hmwp_app_logo"><img src="<?php echo esc_url( _HMWP_ASSETS_URL_ . 'img/microsoft-logo.png' ); ?>" alt=""></a>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/setting-up-two-factor-authentication-2fa-for-wordpress-using-mobile-apps/#ghost-lastpass-authenticator' ); ?>" target="_blank" class="hmwp_app_logo"><img src="<?php echo esc_url( _HMWP_ASSETS_URL_ . 'img/lastpass-logo.png' ); ?>" alt=""></a>
                                    </div>
                                </li>
                                <li>
                                    <p><?php esc_html_e( 'Scan the provided code using your authenticator app to link this account.', 'hide-my-wp' ); ?></p>
                                    <p><?php esc_html_e( 'Some authenticator apps permit you to manually input the text version.', 'hide-my-wp' ); ?></p>
                                    <p class="htmp_app_key_wrapper"><code><?php echo esc_html( $view->options['key'] ); ?></code></p>
                                </li>
                                <li>
                                    <?php esc_html_e( 'Type in the one-time code from your chosen authentication app to finalize the setup.', 'hide-my-wp' ); ?>
                                    <p class="htmp_app_auth_wrapper">
                                        <input type="hidden" name="hmwp_totp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'hmwp_totp_submit' ) ) ?>"/>
                                        <input type="hidden" name="hmwp_totp_referer" value="<?php echo esc_url( remove_query_arg( '_wp_http_referer' ) ); ?>" />
                                        <input type="hidden" name="hmwp_totp_action" value="hmwp_totp_submit"/>
                                        <input type="hidden" name="hmwp_totp_key" value="<?php echo esc_attr( $view->options['key'] ); ?>" />
                                        <input type="hidden" name="hmwp_totp_user_id" value="<?php echo esc_attr( $view->options['user']->ID ); ?>" />
                                        <label for="hmwp_totp_authcode">
                                            <?php echo esc_html__( 'Authentication Code:', 'hide-my-wp' ); ?>
                                            <input type="tel" name="hmwp_totp_authcode" id="hmwp_totp_authcode" class="input" value="" size="20" pattern="[0-9 ]*" placeholder="<?php echo esc_attr( sprintf( /* translators: %s: Example authentication code. */ __( 'e.g. %s', 'hide-my-wp' ), '123456' ) ); ?>" />
                                        </label>
                                        <input id="hmwp_totp_submit" type="button" class="button button-primary" value="<?php echo esc_attr__( 'Submit', 'hide-my-wp' ); ?>" />
                                    </p>
                                </li>
                            </ol>
                        </td>
                    </table>

                <?php } elseif ( isset( $view->options['user'] ) && $view->options['user'] <> '' ) { ?>
                    <div class="hmwp_title">
                        <?php esc_html_e( 'Secret key is configured and registered.', 'hide-my-wp' ); ?>
                    </div>
                    <div class="hmwp_description">
                        <?php esc_html_e( 'It is not possible to view it again for security reasons.', 'hide-my-wp' ); ?>
                    </div>
                    <div class="hmwp_description">
                        <?php esc_html_e( 'You will have to re-scan the QR code on all devices as the previous codes will stop working.', 'hide-my-wp' ); ?>
                    </div>
                    <div style="margin: 10px 0">
                        <input type="hidden" name="hmwp_totp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'hmwp_totp_reset' ) ); ?>"/>
                        <input type="hidden" name="hmwp_totp_referer" value="<?php echo esc_url( remove_query_arg( '_wp_http_referer' ) ); ?>" />
                        <input type="hidden" name="hmwp_totp_action" value="hmwp_totp_reset"/>
                        <input type="hidden" name="hmwp_totp_user_id" value="<?php echo esc_attr( $view->options['user']->ID ); ?>" />

                        <input id="hmwp_totp_reset" type="button" class="button" value="<?php echo esc_attr__( 'Reset Key', 'hide-my-wp' ); ?>" />

                        <?php
                        //Show the Codes block
                        $view->show( 'blocks/Codes' );
                        ?>
                    </div>
                <?php } ?>
            <?php } ?>
        </td>
    </tr>
</table>