<?php defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' ); ?>
<?php if ( ! isset( $view ) ) { return; } ?>
<div class="d-flex flex-row flex-nowrap flex-grow-1 m-0 p-0">
    <div class="flex-grow-1 hmwp_flex m-0 py-0 px-4">

        <div class="col-12 p-0 m-0">
            <div class="col-lg-8 my-0 mx-auto p-0">

                <div class="col-12 p-0 m-0 mt-5 mb-3 text-center">
                    <div class="group_autoload d-flex justify-content-center btn-group btn-group-lg mt-3" role="group">
                        <div class="font-weight-bold" style="font-size: 1.2rem">
                            <span class="hmwp_logo hmwp_logo_30 align-top mr-2"></span><?php echo esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ) . ' - ' . esc_html__( "Advanced Pack", 'hide-my-wp' ); ?>
                        </div>
                    </div>
                    <div class="text-center mt-4"><?php echo esc_html__( "This amazing feature isn't included in the basic plugin. Want to unlock it? Simply install or activate the Advanced Pack and enjoy the new security features.", 'hide-my-wp' ); ?></div>
                    <div class="text-center mt-4"><?php echo esc_html__( "Let's take your security to the next level!", 'hide-my-wp' ); ?></div>
                </div>

                <div class="col-12 row m-0 p-0 my-5 text-center">
                    <form method="post" class="col-12 p-0 m-0">
						<?php wp_nonce_field( 'hmwp_advanced_install', 'hmwp_nonce' ) ?>
                        <input type="hidden" name="action" value="hmwp_advanced_install"/>
                        <button type="submit" class="btn btn-success m-auto">
							<?php echo esc_html__( "Install/Activate", 'hide-my-wp' ) . ' ' . esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ) . ' - ' . esc_html__( "Advanced Pack", 'hide-my-wp' ) ?>
                        </button>
                    </form>
                    <div class="col-12 text-center mt-4">
                        <div class="text-black-50 small mx-auto"><?php echo esc_html__( "(* the plugin has no extra cost, gets installed / activated automatically inside WP when you click the button, and uses the same account)", 'hide-my-wp' ); ?></div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

