<?php
defined('ABSPATH') || die('Cheating uh?');
if(!isset($view)) return;
?>

<?php if(!empty($view->codes)){?>
<table>
    <tr>
        <th>
            <?php echo esc_html__( 'Backup Codes', 'hide-my-wp' ); ?>
        </th>
        <td>
            <div id="hmwp_codes_wrapper">
                <div class="hmwp_title"><?php echo esc_html__('Download Recovery Codes', 'hide-my-wp') ?> <span style="color: darkgrey; font-size: x-small"><?php echo esc_html__('Optional', 'hide-my-wp') ?></span></div>
                <span class="hmwp_description">
                    <?php
                    echo esc_html(
                            sprintf(
                            /* translators: %s: Number of backup codes. */
                                    _n(
                                            'Utilize this %s code for login in case you lose access to your website dashboard. Each code can be employed only once.',
                                            'Utilize one of these unique %s codes for login in case you lose access to your website dashboard. Each code can be employed only once.',
                                            count( $view->codes ),
                                            'hide-my-wp'
                                    ),
                                    count( $view->codes )
                            )
                    );
                    ?>
                </span>
                <ul class="hmwp_codes_unused">
                    <?php  foreach ($view->codes as $code){ ?>
                        <li><?php echo esc_html( $code ) ?></li>
                    <?php } ?>
                </ul>
                <p>
                    <a id="hmwp_codes_download" class="button button-secondary hide-if-no-js" href="<?php echo esc_attr( $view->downloadLinks ) ?>" download="2fa-backup-codes.txt"><?php esc_html_e( 'Download Codes', 'hide-my-wp' ); ?></a>
                    <button id="hmwp_codes_finalize" class="button button-primary" ><?php esc_html_e( 'Finalize', 'hide-my-wp' ); ?></button>
                <p>
            </div>
        </td>
    </tr>
</table>
<?php }else{?>
    <input type="hidden" name="hmwp_codes_nonce" value="<?php echo esc_attr( wp_create_nonce( 'hmwp_codes_generate' ) ) ?>"/>
    <input type="hidden" name="hmwp_codes_referer" value="<?php echo esc_url( remove_query_arg( '_wp_http_referer' ) ); ?>>" />
    <input type="hidden" name="hmwp_codes_action" value="hmwp_codes_generate"/>
    <input type="hidden" name="hmwp_codes_user_id" value="<?php echo esc_attr( $view->options['user']->ID ); ?>" />
    <button id="hmwp_codes_generate" type="button" class="button-secondary hide-if-no-js">
        <?php esc_html_e( 'Generate Backup Codes', 'hide-my-wp' ); ?>
    </button>
<?php }?>
