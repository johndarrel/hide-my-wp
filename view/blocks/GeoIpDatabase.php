<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' ); ?>
<?php if ( ! isset( $view ) ) {
	return;
} ?>
<div class="card col-sm-12 m-0 mb-2 p-0 rounded-0">
    <div class="card-body f-gray-dark text-center">
        <h4 class="card-title"><?php echo esc_html__( 'Download GeoIP Database', 'hide-my-wp' ); ?></h4>
        <div class="border-top mt-2 pt-2"></div>
        <div class="col-sm-12 row mb-1 ml-1 p-2">

            <div class="col-sm-12 my-2 p-0 text-center">
                <div class="text-black-50 mt-2"><?php echo esc_html__( 'Download the latest Geo Country Database to enable the Geo Security feature.', 'hide-my-wp' ); ?></div>
                <div class="my-3 text-info"><?php echo esc_html__( 'Note: This database includes the most up-to-date records of IP addresses and their corresponding country origins.', 'hide-my-wp' ) ?> </div>
                <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_change_in_cache' ) ) { ?>
                    <form method="POST">
                        <?php wp_nonce_field( 'hmwp_geo_download', 'hmwp_nonce' ) ?>
                        <input type="hidden" name="action" value="hmwp_geo_download"/>
                        <button type="submit" class="btn rounded-0 btn-default px-4"><?php echo esc_html__( 'Update Database', 'hide-my-wp' ); ?></button>
                    </form>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
