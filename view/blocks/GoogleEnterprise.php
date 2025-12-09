<?php defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' ); ?>
<?php if ( ! isset( $view ) ) {
	return;
} ?>
<div class="card col-sm-12 m-0 mb-2 p-0 rounded-0">
    <div class="card-body f-gray-dark text-center">
        <h4 class="card-title"><?php echo esc_html__( 'Google reCaptcha Enterprise', 'hide-my-wp' ); ?></h4>
        <div class="border-top mt-2 pt-2"></div>
        <div class="col-sm-12 row mb-1 ml-1 p-2">

            <div class="col-sm-12 my-2 p-0 text-center">
                <form id="hmwp_google_enterprise" method="POST">
	                <?php wp_nonce_field( 'hmwp_google_enterprise', 'hmwp_nonce' ) ?>
                    <input type="hidden" name="action" value="hmwp_google_enterprise"/>

                    <div class="checker text-center">
                        <div class="col-sm-12 p-0 py-2 switch switch-sm">

                            <input type="hidden" name="brute_use_google_enterprise" value="0"/>
                            <input type="checkbox" id="brute_use_google_enterprise" name="brute_use_google_enterprise" onchange="jQuery('form#hmwp_google_enterprise').submit()" class="switch nopopup" <?php echo( HMWP_Classes_Tools::getOption( 'brute_use_google_enterprise' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                            <label for="brute_use_google_enterprise">
                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website'). '/kb/google-recaptcha-enterprise-protection/') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                            </label>
                        </div>
                    </div>
                </form>

                <div class="my-3 text-info"><?php echo esc_html__( 'Switch between Google reCaptcha Classic and Google reCaptcha Enterprise based on your Google Console setup.', 'hide-my-wp' ) ?> </div>
            </div>
        </div>
    </div>
</div>

