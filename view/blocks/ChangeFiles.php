<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php if ( ! isset( $view ) ) {
	return;
} ?>
<div id="hmwp_changepathsincache" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><?php echo esc_html__( 'Ghost Mode', 'hide-my-wp' ) ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="my-3"><?php echo esc_html__( 'Click to run the process to change the paths in the cache files.', 'hide-my-wp' ) ?> </div>
                <div class="my-3 text-info"><?php echo esc_html__( 'Note! The plugin will use WP cron to change the paths in background once the cache files are created.', 'hide-my-wp' ) ?> </div>
                <form method="POST">
					<?php wp_nonce_field( 'hmwp_changepathsincache', 'hmwp_nonce' ) ?>
                    <input type="hidden" name="action" value="hmwp_changepathsincache"/>
                    <button type="submit" class="btn btn-success d-inline-block ml-2"><?php echo esc_html__( 'Change Paths', 'hide-my-wp' ); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
