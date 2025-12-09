<?php defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' ); ?>
<?php if ( ! isset( $view ) ) { return; } ?>
<noscript>
    <style>#hmwp_wrap .tab-panel:not(.tab-panel-first) {
            display: block
        }</style>
</noscript>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
	<?php echo $view->getAdminTabs( HMWP_Classes_Tools::getValue( 'page', 'hmwp_log' ) ); ?>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 p-0 pr-2 mr-2 mb-3">

            <form method="POST">
				<?php wp_nonce_field( 'hmwp_logsettings', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_logsettings"/>

                <div id="log" class="card col-sm-12 p-0 m-0 tab-panel ">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Events Log Settings', 'hide-my-wp' ); ?>
                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/events-log-report/#ghost-events-log-report-on-wordpress-dashboard' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body p-2 m-0">
                        <div class="col-sm-12 row mb-1 py-3 mx-2 hmwp_pro">
                            <div class="box">
                                <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                            </div>
                            <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <img src="<?php echo esc_url( _HMWP_ASSETS_URL_ . 'img/events.png' ) ?>" style="width: 100%" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0 0 8px -3px #444;">
                        <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__( 'Save', 'hide-my-wp' ); ?></button>
                    </div>
                </div>

            </form>
        </div>
        <div class="hmwp_col hmwp_col_side p-0 pr-2 mr-2">
            <div class="card col-sm-12 m-0 p-0 rounded-0">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="card-title"><?php echo esc_html__( 'Events Log', 'hide-my-wp' ); ?></h3>
                    <div class="text-info mb-3"><?php echo esc_html__( "The Events Log Report will document every action users take when trying to log in or are already logged in to your site (for the last 30 days), so you’ll know who does what on your site.", 'hide-my-wp' ); ?></div>
                    <div class="text-black-50 mb-3"><?php echo esc_html__( "This refers to actions that could impact your site’s security. WP Ghost will NOT log users’ actions such as clicking on a Menu or other similar, everyday actions that a user regularly takes in the frontend of a site.", 'hide-my-wp' ); ?></div>
                </div>
            </div>
            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-left border-bottom">
                    <h3 class="card-title"><?php echo esc_html__( 'Features', 'hide-my-wp' ); ?></h3>
                    <ul class="text-info" style="margin-left: 16px; list-style: circle;">
                        <li class="mb-2"><?php echo esc_html__( "Monitor, track and log events on your website.", 'hide-my-wp' ); ?></li>
                        <li class="mb-2"><?php echo esc_html__( "Know what the other users are doing on your website.", 'hide-my-wp' ); ?></li>
                        <li class="mb-2"><?php echo esc_html__( "You can set to receive security alert emails and prevent data loss.", 'hide-my-wp' ); ?></li>
                        <li><?php echo esc_html__( "Compatible with all themes and plugins.", 'hide-my-wp' ); ?></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
