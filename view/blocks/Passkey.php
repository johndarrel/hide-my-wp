<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) {
    return;
}
?>
<table>
    <tr>
        <th>
            <?php echo esc_html__( 'Passkey Setup', 'hide-my-wp' ); ?>
        </th>
        <td>
            <div class="hmwp-2fa-block hmwp-2fa-passkey">

                <?php if ( empty( $view->options['credentials'] ) ) : ?>
                    <p><?php esc_html_e( 'No passkeys registered yet.', 'hide-my-wp' ); ?></p>
                <?php else : ?>
                    <input type="hidden" name="hmwp_passkey_remove_nonce" value="<?php echo esc_attr( wp_create_nonce( 'hmwp_passkey_remove' ) ) ?>"/>
                    <input type="hidden" name="hmwp_passkey_remove_action" value="hmwp_passkey_remove"/>
                    <input type="hidden" name="hmwp_passkey_remove_referer" value="<?php echo esc_url( remove_query_arg( '_wp_http_referer' ) ); ?>"/>
                    <input type="hidden" name="hmwp_passkey_remove_user_id" value="<?php echo esc_attr( $view->options['user']->ID ); ?>"/>
                    <ul class="hmwp-passkey-list">
                        <?php foreach ( $view->options['credentials'] as $cred ) : ?>
                            <li id="hmwp-passkey-<?php echo esc_attr( $cred['id'] ); ?>"
                                style="line-height: 30px; width: 100%; margin-bottom: 10px;">
                                <strong><?php echo esc_html( $cred['nickname'] ?? __( 'Unnamed device', 'hide-my-wp' ) ); ?></strong>
                                <small><?php echo esc_html( date_i18n( get_option( 'date_format' ), $cred['created_at'] ?? time() ) ); ?></small>
                                <button class="button hmwp_passkey_delete" data-id="<?php echo esc_attr( $cred['id'] ); ?>"
                                        style="margin-left: 10px;">
                                    <?php esc_html_e( 'Remove', 'hide-my-wp' ); ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                <?php endif; ?>

                <input type="hidden" name="hmwp_passkey_nonce" value="<?php echo esc_attr( wp_create_nonce( 'hmwp_passkey_submit' ) ) ?>"/>
                <input type="hidden" name="hmwp_passkey_user_id" value="<?php echo esc_attr( $view->options['user']->ID ); ?>"/>
                <input type="hidden" name="hmwp_passkey_referer" value="<?php echo esc_url( remove_query_arg( '_wp_http_referer' ) ); ?>"/>
                <input type="hidden" name="hmwp_passkey_action" value="hmwp_passkey_submit"/>

                <input type="hidden" name="hmwp_passkey_register_nonce" value="<?php echo esc_attr( wp_create_nonce( 'hmwp_passkey_register' ) ) ?>"/>
                <input type="hidden" name="hmwp_passkey_register_action" value="hmwp_passkey_register"/>

                <button class="button button-primary" id="hmwp_passkey_submit">
                    <?php esc_html_e( 'Add Passkey', 'hide-my-wp' ); ?>
                </button>
            </div>
        </td>
    </tr>
</table>
