<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $message ) || ! isset( $type ) || ! isset( $ignore ) ) {
    return;
} ?>
<div class="hmwp_notice <?php echo esc_attr( $type ) ?>">
    <?php if ( function_exists( 'wp_create_nonce' ) && $type <> 'success' && $ignore ) { ?>
        <a href="<?php echo esc_url( add_query_arg( array(
                'hmwp_nonce' => wp_create_nonce( 'hmwp_ignoreerror' ),
                'action'     => 'hmwp_ignoreerror',
                'hash'       => strlen( $message )
        ) ) ) ?>" style="float: right; color: gray; text-decoration: none; font-size: 0.85rem;">X</a>
    <?php } ?>

    <?php echo wp_kses_post( $message ) ?>
</div>
