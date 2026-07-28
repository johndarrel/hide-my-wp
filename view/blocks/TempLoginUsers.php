<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php
if ( ! isset( $view ) ) {
    return;
}

$tempLoginModel = HMWP_Classes_ObjController::getClass('HMWP_Models_Templogin');
$users = $tempLoginModel->getTempUsers();

if ( ! empty( $users ) ) {
    $pagination = HMWP_Classes_Tools::getPagination( $users );
    ?>

    <table class="table table-striped">
        <tr>
            <th><?php echo esc_html__( 'User', 'hide-my-wp' ); ?></th>
            <th><?php echo esc_html__( 'Role', 'hide-my-wp' ); ?></th>
            <th><?php echo esc_html__( 'Last Access', 'hide-my-wp' ); ?></th>
            <th><?php echo esc_html__( 'Expires', 'hide-my-wp' ); ?></th>
            <th><?php echo esc_html__( 'Options', 'hide-my-wp' ); ?></th>
        </tr>

        <?php foreach ( $users as $user ) : ?>
            <?php
            $user->details = $tempLoginModel->getUserDetails( $user );

            // If there is a multisite user
            if ( isset( $user->details->user_blog_id ) ) {
                $user->details->user_role_name .= '<br>' . esc_url( get_home_url( $user->details->user_blog_id ) );
            }

            // Build Expires output (may contain <br> and <span>)
            $expires = false;

            if ( isset( $user->details->expire ) && (int) $user->details->expire > 0 ) {
                $expires = $tempLoginModel->timeElapsed( $user->details->expire );
            } else {
                if ( isset( $tempLoginModel->expires[ $user->details->expire ] ) ) {
                    $expires  = $tempLoginModel->expires[ $user->details->expire ]['label'];
                    $expires .= '<br /><span class="text-black-50 small">(' . esc_html__( 'after first access', 'hide-my-wp' ) . ')</span>';
                }
            }
            ?>

            <tr>
                <td>
                    <div><span>
						<?php if ( ! empty( $user->first_name ) ) { ?>
                            <span><?php echo esc_html( $user->first_name ); ?></span>
                        <?php } ?>

                            <?php if ( ! empty( $user->last_name ) ) { ?>
                                <span> <?php echo esc_html( $user->last_name ); ?></span>
                            <?php } ?>

						(<span class="user-login"><?php echo esc_html( $user->user_login ); ?></span>)<br />

						<?php if ( ! empty( $user->user_email ) ) { ?>
                            <p class="inline-block pt-1 font-medium text-black-50"><?php echo esc_html( $user->user_email ); ?></p><br />
                        <?php } ?>
					</span></div>
                </td>

                <td>
                    <?php
                    // user_role_name may contain <br> (multisite)
                    echo wp_kses( (string) $user->details->user_role_name, [ 'br' => [] ] );
                    ?>
                </td>

                <td><?php echo esc_html( (string) $user->details->last_login ); ?></td>

                <td class="hmwp-status-<?php echo esc_attr( strtolower( (string) $user->details->status ) ); ?> pl-4">
                    <?php
                    // $expires can contain <br> and <span>
                    echo wp_kses(
                            (string) $expires,
                            [
                                    'br'   => [],
                                    'span' => [ 'class' => [] ],
                            ]
                    );
                    ?>
                </td>

                <td class="p-2">
                    <div class="row m-0 p-0">

                        <?php if ( ! empty( $user->details->is_active ) ) { ?>
                            <form method="POST" class="col-3 m-0 p-1">
                                <?php wp_nonce_field( 'hmwp_templogin_block', 'hmwp_nonce' ); ?>
                                <input type="hidden" name="action" value="hmwp_templogin_block" />
                                <input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>" />
                                <button type="submit" class="btn btn-link btn-sm m-0 p-0">
                                    <i class="fa fa-unlock" title="<?php echo esc_attr__( 'Lock user', 'hide-my-wp' ); ?>"></i>
                                </button>
                            </form>
                        <?php } else { ?>
                            <form method="POST" class="col-3 m-0 p-1">
                                <?php wp_nonce_field( 'hmwp_templogin_activate', 'hmwp_nonce' ); ?>
                                <input type="hidden" name="action" value="hmwp_templogin_activate" />
                                <input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>" />
                                <button type="submit" class="btn btn-link btn-sm m-0 p-0">
                                    <i class="fa fa-lock" title="<?php echo esc_attr__( 'Reactivate user for 1 day', 'hide-my-wp' ); ?>"></i>
                                </button>
                            </form>
                        <?php } ?>

                        <div class="col-3 m-0 p-1">
                            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'hmwp_templogin', 'action' => 'hmwp_update', 'user_id' => $user->ID ] ) ); ?>"
                               class="btn btn-link btn-sm m-0 p-0">
                                <i class="fa fa-edit" title="<?php echo esc_attr__( 'Edit user', 'hide-my-wp' ); ?>"></i>
                            </a>
                        </div>

                        <form method="POST" class="col-3 m-0 p-1">
                            <?php wp_nonce_field( 'hmwp_templogin_delete', 'hmwp_nonce' ); ?>
                            <input type="hidden" name="action" value="hmwp_templogin_delete" />
                            <input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>" />
                            <button type="submit"
                                    class="btn btn-link btn-sm m-0 p-0"
                                    onclick="return confirm('<?php echo esc_js( __( 'Do you want to delete temporary user?', 'hide-my-wp' ) ); ?>');">
                                <i class="fa fa-close text-danger" title="<?php echo esc_attr__( 'Delete user', 'hide-my-wp' ); ?>"></i>
                            </button>
                        </form>

                        <?php if ( ! empty( $user->details->is_active ) ) { ?>
                            <div class="col-3 m-0 p-1">
                                <button type="button"
                                        id="text-<?php echo esc_attr( $user->ID ); ?>"
                                        class="btn btn-link btn-sm m-0 p-0 hmwp_clipboard_copy"
                                        data-clipboard-text="<?php echo esc_attr( $user->details->templogin_url ); ?>">
                                    <i class="fa fa-link" title="<?php echo esc_attr__( 'Copy Link', 'hide-my-wp' ); ?>"></i>
                                </button>
                            </div>
                        <?php } ?>

                    </div>
                </td>
            </tr>

        <?php endforeach; ?>
    </table>

    <?php echo $pagination; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<?php } else { ?>

    <table class="table table-striped">
        <tr>
            <th><?php echo esc_html__( 'User', 'hide-my-wp' ); ?></th>
            <th><?php echo esc_html__( 'Role', 'hide-my-wp' ); ?></th>
            <th><?php echo esc_html__( 'Last Access', 'hide-my-wp' ); ?></th>
            <th><?php echo esc_html__( 'Expires', 'hide-my-wp' ); ?></th>
            <th><?php echo esc_html__( 'Options', 'hide-my-wp' ); ?></th>
        </tr>
        <tr>
            <td colspan="5">
                <?php echo esc_html__( 'No temporary logins.', 'hide-my-wp' ); ?>
                <button type="button" class="btn btn-link btn-sm text-dark inline p-0" style="vertical-align: top" onclick="jQuery('#hmwp_templogin_modal_new').modal('show');">
                    <?php echo esc_html__( 'Create New Temporary Login', 'hide-my-wp' ); ?>
                </button>
            </td>
        </tr>
    </table>

<?php } ?>