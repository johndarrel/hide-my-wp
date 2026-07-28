<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php
if ( ! isset( $view ) ) {
    return;
}

$ips = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->getBlockedIps();
$pagination = '';

if ( ! empty( $ips ) ) {
    $pagination = HMWP_Classes_Tools::getPagination( $ips );
?>
    <div class="py-1">
        <div class="my-1">
            <form method="POST">
                <?php wp_nonce_field( 'hmwp_deleteallips', 'hmwp_nonce' ); ?>
                <input type="hidden" name="action" value="hmwp_deleteallips"/>
                <button type="submit" class="btn rounded-0 btn-default save py-1"><?php echo esc_html__( 'Unlock all', 'hide-my-wp' ); ?></button>
                <a href="javascript:void(0);" onclick="location.reload()" class="btn rounded-0 btn-link py-1"><?php echo esc_html__( 'Refresh', 'hide-my-wp' ); ?></a>
            </form>
        </div>
    </div>
<?php }?>

<table class="table table-striped">
    <tr>
        <th style="width: 20%"><strong><?php echo esc_html__( 'IP', 'hide-my-wp' ); ?></strong></th>
        <th><?php echo esc_html__( 'Fail Attempts', 'hide-my-wp' ); ?></th>
        <th><?php echo esc_html__( 'Expires', 'hide-my-wp' ); ?></th>
        <th style="width: 10%"><?php echo esc_html__( 'Options', 'hide-my-wp' ); ?></th>
    </tr>

    <?php
    if ( ! empty( $ips ) ) {
        foreach ( $ips as $ip ) { ?>
            <tr>
                <td><?php echo esc_html( $ip['ip'] ); ?></td>
                <td><?php echo esc_html( $ip['attempts'] ); ?></td>
                <td><?php echo esc_html( $ip['remaining'] ); ?></td>
                <td class="p-2">
                    <form method="POST">
                        <?php wp_nonce_field( 'hmwp_deleteip', 'hmwp_nonce' ); ?>
                        <input type="hidden" name="action" value="hmwp_deleteip"/>
                        <input type="hidden" name="ip" value="<?php echo esc_attr( $ip['ip'] ); ?>"/>
                        <input type="submit" class="btn rounded-0 btn-sm btn-light save no-p-v" value="<?php echo esc_attr__( 'Unlock', 'hide-my-wp' ); ?>"/>
                    </form>
                </td>
            </tr>
        <?php } ?>
    <?php } else { ?>
        <tr>
            <td colspan="4" style="min-height: 100px;">
                <?php echo esc_html__( 'No blocked IPs found', 'hide-my-wp' ); ?>
            </td>
        </tr>
    <?php } ?>
</table>

<?php echo $pagination; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

