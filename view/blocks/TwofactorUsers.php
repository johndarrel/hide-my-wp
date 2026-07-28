<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php
if ( ! isset( $view ) ) {
    return;
}

$logs = HMWP_Classes_ObjController::getClass('HMWP_Controllers_Twofactor')->getLogs();
$pagination = HMWP_Classes_Tools::getPagination( $logs );
?>
<table class="table table-striped">
    <tr>
        <th><?php echo esc_html__( 'Email', 'hide-my-wp' ); ?></th>
        <th><?php echo esc_html__( 'Login Type', 'hide-my-wp' ); ?></th>
        <th><?php echo esc_html__( 'Status', 'hide-my-wp' ); ?></th>
        <th><?php echo esc_html__( 'Last Access', 'hide-my-wp' ); ?></th>
    </tr>

    <?php if ( ! empty( $logs ) ) : ?>
        <?php foreach ( $logs as $log ) : ?>
            <?php
            $user = $log['user'];

            $status_label = ! empty( $log['success'] )
                    ? __( 'Success', 'hide-my-wp' )
                    : __( 'Failed', 'hide-my-wp' );

            $status_class = ! empty( $log['success'] ) ? 'text-success' : 'text-danger';
            ?>

            <tr>
                <td>
                    <div><span>
						<?php if ( ! empty( $user->first_name ) ) : ?>
                            <span><?php echo esc_html( $user->first_name ); ?></span>
                        <?php endif; ?>

                            <?php if ( ! empty( $user->last_name ) ) : ?>
                                <span> <?php echo esc_html( $user->last_name ); ?></span>
                            <?php endif; ?>

						(<span class="user-login"><?php echo esc_html( $user->user_login ); ?></span>)<br />

						<?php if ( ! empty( $user->user_email ) ) : ?>
                            <p class="inline-block pt-1 font-medium text-black-50">
								<?php echo esc_html( $user->user_email ); ?>
							</p>
                        <?php endif; ?>
					</span></div>
                </td>

                <td class="p-2">
                    <?php echo isset( $log['mode'] ) ? esc_html( (string) $log['mode'] ) : ''; ?>
                </td>

                <td class="<?php echo esc_attr( $status_class ); ?> pl-4">
                    <?php echo esc_html( $status_label ); ?>
                </td>

                <td>
                    <?php echo isset( $log['last_login'] ) ? esc_html( (string) $log['last_login'] ) : ''; ?>
                </td>
            </tr>

        <?php endforeach; ?>
    <?php else : ?>
        <tr>
            <td colspan="4" style="min-height: 100px;">
                <?php echo esc_html__( 'No logins with 2FA.', 'hide-my-wp' ); ?>
            </td>
        </tr>
    <?php endif; ?>
</table>

<?php echo $pagination; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
