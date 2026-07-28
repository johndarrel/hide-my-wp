<?php defined( 'ABSPATH' ) || die( 'Cheating uh?' );
if ( ! isset( $view ) ) {
	return;
}

/**
 * @var $wp_roles WP_Roles
 */
global $wp_roles;

$allroles = array();
if ( function_exists( 'wp_roles' ) ) {
	$allroles = wp_roles()->get_names();
}
?>
<noscript>
    <style>#hmwp_wrap .tab-panel:not(.tab-panel-first) {
            display: block
        }</style>
</noscript>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
    <?php
    $view->getAdminTabs( HMWP_Classes_Tools::getValue( 'page' ) );

    $current_tab = HMWP_Classes_Tools::getValue( 'tab' );
    $subtabs = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Menu' )->getSubMenu( HMWP_Classes_Tools::getValue( 'page' ) );
    $tabs = array_column( $subtabs, 'tab' );

    if ( ! $current_tab || ( ! empty( $tabs ) && ! in_array( $current_tab, $tabs, true ) ) ) {
        $current_tab = reset( $tabs );
    }
    ?>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 p-0 pr-2 mr-2 mb-3">

			<?php do_action( 'hmwp_tweaks_beginning' ) ?>

            <form method="POST">
				<?php wp_nonce_field( 'hmwp_tweakssettings', 'hmwp_nonce' ) ?>
                <input type="hidden" name="action" value="hmwp_tweakssettings"/>

				<?php do_action( 'hmwp_tweaks_form_beginning' ) ?>

                <div id="redirects" style="<?php echo ( $current_tab === 'redirects' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Redirects', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/redirects/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) { ?>
                            <div class="card-body">
                                <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
                                    <?php /* translators: 1: Opening <a> tag to the settings page, 2: Closing </a> tag, 3: Opening <a> tag to the settings page, 4: Closing </a> tag */
                                    echo wp_kses_post( sprintf( __( 'First, you need to activate the %1$sLite Mode%2$s or %3$sGhost Mode%4$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>' ) ); ?>                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="card-body">
                                <div class="col-sm-12 row py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__( 'Redirect Hidden Paths', 'hide-my-wp' ); ?>:</div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group mb-1">
                                        <select name="hmwp_url_redirect" class="selectpicker form-control">
                                            <option value="." <?php selected( '.', HMWP_Classes_Tools::getOption( 'hmwp_url_redirect' ) ) ?>><?php echo esc_html__( "Front page", 'hide-my-wp' ) ?></option>
                                            <option value="404" <?php selected( '404', HMWP_Classes_Tools::getOption( 'hmwp_url_redirect' ) ) ?> ><?php echo esc_html__( "404 page", 'hide-my-wp' ) ?></option>
                                            <option value="NFError" <?php selected( 'NFError', HMWP_Classes_Tools::getOption( 'hmwp_url_redirect' ) ) ?> ><?php echo esc_html__( "404 HTML Error", 'hide-my-wp' ) ?></option>
                                            <option value="NAError" <?php selected( 'NAError', HMWP_Classes_Tools::getOption( 'hmwp_url_redirect' ) ) ?> ><?php echo esc_html__( "403 HTML Error", 'hide-my-wp' ) ?></option>
                                            <?php
                                            $pages = get_pages( array( 'number' => 50 ) );
                                            foreach ( $pages as $page ) {
                                                if ( $page->post_title <> '' ) { ?>
                                                    <option value="<?php echo esc_attr( $page->post_name ) ?>" <?php echo selected( $page->post_name, HMWP_Classes_Tools::getOption( 'hmwp_url_redirect' ) ) ?> ><?php echo esc_html( $page->post_title ) ?></option>
                                            <?php	}
                                            } ?>
                                        </select>
                                        <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/redirects/#ghost-redirect-hidden-paths' ) ?>" target="_blank" class="position-absolute float-right" style="right: 40px;top: 25%;"><i class="dashicons dashicons-editor-help"></i></a>
                                    </div>
                                    <div class="p-1">
                                        <div class="text-black-50 small"><?php echo esc_html__( 'Redirect the protected paths /wp-admin, /wp-login to a Page or trigger an HTML Error.', 'hide-my-wp' ); ?></div>
                                        <div class="text-black-50 small"><?php echo esc_html__( 'You can create a new page and come back to choose to redirect to that page.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_do_redirects" value="0"/>
                                            <input type="checkbox" id="hmwp_do_redirects" name="hmwp_do_redirects" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_do_redirects' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_do_redirects"><?php echo esc_html__( 'Do Login & Logout Redirects', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/redirects/#ghost-login-logout-redirects' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Add redirects for the logged users based on user roles.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 py-3 m-0 hmwp_do_redirects">
                                    <?php $urlRedirects = HMWP_Classes_Tools::getOption( 'hmwp_url_redirects' ); ?>
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li class="nav-item m-0">
                                            <a class="nav-link active" data-toggle="tab" href="#default" role="tab" aria-controls="default" aria-selected="true"><?php echo esc_html__( "Default", 'hide-my-wp' ) ?></a>
                                        </li>
                                        <?php if ( ! empty( $allroles ) ) { ?>
                                            <li class="nav-item dropdown m-0">
                                                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><?php echo esc_html__( "User Role", 'hide-my-wp' ) ?></a>
                                                <div class="dropdown-menu" style="height: auto; max-height: 200px; overflow-x: hidden;">
                                                    <?php foreach ( $allroles as $role => $name ) { ?>
                                                        <a class="dropdown-item" data-toggle="tab" href="#nav-<?php echo esc_attr( $role ) ?>" role="tab" aria-controls="nav-<?php echo esc_attr( $role ) ?>" aria-selected="false"><?php echo esc_html( $name ) ?></a>
                                                    <?php } ?>
                                                </div>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                    <div class="tab-content border-right border-left border-bottom p-0 m-0">
                                        <div class="tab-pane show active" id="default" role="tabpanel" aria-labelledby="nav-home-tab">

                                            <div class="col-sm-12 row py-3 m-0">
                                                <div class="col-sm-4 p-0 py-2 font-weight-bold">
                                                    <?php echo esc_html__( 'Login Redirect URL', 'hide-my-wp' ); ?>:
                                                    <div class="small text-black-50"><?php echo esc_html__( "e.g.", 'hide-my-wp' ) . ' ' . esc_url(admin_url( '', 'relative' )); ?></div>
                                                </div>
                                                <div class="col-sm-8 p-0 input-group ">
                                                    <input type="text" class="form-control  mt-2" name="hmwp_url_redirects[default][login]" value="<?php echo( isset( $urlRedirects['default']['login'] ) ? esc_url( $urlRedirects['default']['login'] ) : '' ) ?>"/>
                                                    <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/redirects/#ghost-login-logout-redirects' ) ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 30%;"><i class="dashicons dashicons-editor-help"></i></a>
                                                </div>
                                            </div>

                                            <div class="col-sm-12 row py-3 mx-0">
                                                <div class="col-sm-4 p-0 py-2 font-weight-bold">
                                                    <?php echo esc_html__( 'Logout Redirect URL', 'hide-my-wp' ); ?>:
                                                    <div class="small text-black-50"><?php echo esc_html__( "e.g. /logout or", 'hide-my-wp' ) . ' ' . esc_url(home_url( '', 'relative' )); ?></div>
                                                </div>
                                                <div class="col-sm-8 p-0 input-group">
                                                    <input type="text" class="form-control  mt-2" name="hmwp_url_redirects[default][logout]" value="<?php echo( isset( $urlRedirects['default']['logout'] ) ? esc_url( $urlRedirects['default']['logout'] ) : '' ) ?>"/>
                                                    <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/redirects/#ghost-login-logout-redirects' ) ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 30%;"><i class="dashicons dashicons-editor-help"></i></a>
                                                </div>
                                            </div>

                                            <div class="p-3">
                                                <div class="p-2 text-danger"><?php /* translators: 1: Line break tag <br /> */ echo wp_kses_post( sprintf( __( 'Make sure the redirect URLs exist on your website. %1$sThe User Role redirect URL has higher priority than the Default redirect URL.', 'hide-my-wp' ), '<br />' ) ); ?></div>                                        </div>
                                        </div>

                                        <?php if ( ! empty( $allroles ) ) {
                                            foreach ( $allroles as $role => $name ) { ?>
                                                <div class="tab-pane" id="nav-<?php echo esc_attr( $role ) ?>" role="tabpanel" aria-labelledby="nav-profile-tab">
                                                    <h5 class="card-title pt-3 pb-1 mx-3 text-black-50 border-bottom border-light"><?php echo esc_html( ucwords( str_replace( '_', ' ', $role ) ) ) . ' ' . esc_html__( "redirects", 'hide-my-wp' ); ?>:</h5>
                                                    <div class="col-sm-12 row py-3 m-0">
                                                        <div class="col-sm-4 p-0 py-2 font-weight-bold">
                                                            <?php echo esc_html__( 'Login Redirect URL', 'hide-my-wp' ); ?>:
                                                            <div class="small text-black-50"><?php echo esc_html__( "e.g.", 'hide-my-wp' ) . ' ' . esc_url(admin_url( '', 'relative' )); ?></div>
                                                        </div>
                                                        <div class="col-sm-8 p-0 input-group">
                                                            <input type="text" class="form-control  mt-2" name="hmwp_url_redirects[<?php echo esc_attr( $role ) ?>][login]" value="<?php echo( isset( $urlRedirects[ $role ]['login'] ) ? esc_url( $urlRedirects[ $role ]['login'] ) : '' ) ?>"/>
                                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/redirects/#ghost-login-logout-redirects' ) ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 30%;"><i class="dashicons dashicons-editor-help"></i></a>
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-12 row py-3 m-0">
                                                        <div class="col-sm-4 p-0 py-2 font-weight-bold">
                                                            <?php echo esc_html__( 'Logout Redirect URL', 'hide-my-wp' ); ?>:
                                                            <div class="small text-black-50"><?php echo esc_html__( "e.g. /logout or", 'hide-my-wp' ) . ' ' . esc_url(home_url( '', 'relative' )); ?></div>
                                                        </div>
                                                        <div class="col-sm-8 p-0 input-group">
                                                            <input type="text" class="form-control  mt-2" name="hmwp_url_redirects[<?php echo esc_attr( $role ) ?>][logout]" value="<?php echo( isset( $urlRedirects[ $role ]['logout'] ) ? esc_url( $urlRedirects[ $role ]['logout'] ) : '' ) ?>"/>
                                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/redirects/#ghost-login-logout-redirects' ) ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 30%;"><i class="dashicons dashicons-editor-help"></i></a>
                                                        </div>
                                                    </div>

                                                    <div class="p-3">
                                                        <div class="p-2 text-danger"><?php /* translators: 1: Line break tag <br /> */ echo wp_kses_post( sprintf( __( 'Make sure the redirect URLs exist on your website. %1$sThe User Role redirect URL has higher priority than the Default redirect URL.', 'hide-my-wp' ), '<br />' ) ); ?></div>                                                </div>
                                                </div>
                                            <?php }
                                        } ?>


                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_logged_users_redirect" value="0"/>
                                            <input type="checkbox" id="hmwp_logged_users_redirect" name="hmwp_logged_users_redirect" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_logged_users_redirect' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_logged_users_redirect"><?php echo esc_html__( 'Redirect Logged Users To Dashboard', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/redirects/#ghost-redirect-to-dashboard' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Automatically redirect the logged in users to the admin dashboard.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div id="sitemap" style="<?php echo ( $current_tab === 'sitemap' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Feed & Sitemap', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/sitemap-feed-and-robots/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) { ?>
                            <div class="card-body">
                                <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
                                    <?php /* translators: 1: Opening <a> tag to the settings page, 2: Closing </a> tag, 3: Opening <a> tag to the settings page, 4: Closing </a> tag */
                                    echo wp_kses_post( sprintf( __( 'First, you need to activate the %1$sLite Mode%2$s or %3$sGhost Mode%4$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>' ) ); ?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="card-body">
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_feed" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_feed" name="hmwp_hide_feed" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_feed' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_hide_feed"><?php echo esc_html__( 'Hide Feed & Sitemap Link Tags', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/sitemap-feed-and-robots/#ghost-hide-link-tags' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( 'Hide the /feed and /sitemap.xml link Tags.', 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_in_feed" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_in_feed" name="hmwp_hide_in_feed" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_in_feed' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_hide_in_feed"><?php echo esc_html__( 'Change Paths in RSS feed', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/sitemap-feed-and-robots/#ghost-change-rss-feed' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php /* translators: 1: Opening <a><strong> tag to RSS feed URL, 2: Closing </strong></a> tag */
                                                echo wp_kses_post( sprintf( __( 'Check the %1$s RSS feed %2$s and make sure the image paths are changed.', 'hide-my-wp' ), '<a href="' . esc_url( site_url() . '/rss' )  . '" target="_blank"><strong>', '</strong></a>' ) ); ?></div>                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_in_sitemap" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_in_sitemap" name="hmwp_hide_in_sitemap" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_in_sitemap' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_hide_in_sitemap"><?php echo esc_html__( 'Change Paths in Sitemaps XML', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/sitemap-feed-and-robots/#ghost-change-sitemap' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php /* translators: 1: Opening <a><strong> tag to sitemap.xml URL, 2: Closing </strong></a> tag */
                                                echo wp_kses_post( sprintf( __( 'Check the %1$s Sitemap XML %2$s and make sure the image paths are changed.', 'hide-my-wp' ), '<a href="' . esc_url( site_url() . '/sitemap.xml' ) . '" target="_blank"><strong>', '</strong></a>' ) ); ?></div>                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hide_in_sitemap">
                                    <div class="checker col-sm-12 row my-2 py-0">
                                        <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                            <input type="hidden" name="hmwp_hide_author_in_sitemap" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_author_in_sitemap" name="hmwp_hide_author_in_sitemap" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_author_in_sitemap' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_hide_author_in_sitemap"><?php echo esc_html__( 'Remove Plugins Authors & Style in Sitemap XML', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/sitemap-feed-and-robots/#ghost-change-sitemap' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "To improve your website's security, consider removing authors and styles that point to WordPress in your sitemap XML.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_robots" value="0"/>
                                            <input type="checkbox" id="hmwp_robots" name="hmwp_robots" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_robots' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_robots"><?php echo esc_html__( 'Hide Paths in Robots.txt', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/sitemap-feed-and-robots/#ghost-change-robots' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php /* translators: 1: Opening <a><strong> tag to robots.txt URL, 2: Closing </strong></a> tag */
                                                echo wp_kses_post( sprintf( __( 'Hide WordPress common paths from %1$s Robots.txt %2$s file.', 'hide-my-wp' ), '<a href="' . esc_url( site_url() . '/robots.txt' ) . '" target="_blank"><strong>', '</strong></a>' ) ); ?></div>                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div id="changes" style="<?php echo ( $current_tab === 'changes' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Change Options', 'hide-my-wp' ); ?></h3>
                        <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) { ?>
                            <div class="card-body">
                                <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
                                    <?php /* translators: 1: Opening <a> tag to the settings page, 2: Closing </a> tag, 3: Opening <a> tag to the settings page, 4: Closing </a> tag */
                                    echo wp_kses_post( sprintf( __( 'First, you need to activate the %1$sLite Mode%2$s or %3$sGhost Mode%4$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>' ) ); ?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="card-body">

                                <div class="col-sm-12 row py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__( 'Simulate CMS', 'hide-my-wp' ); ?>:</div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group mb-1">
                                        <select name="hmwp_emulate_cms" class="selectpicker form-control mb-1">
                                            <option value="" <?php selected('', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?>><?php echo esc_html__("No CMS Simulation", 'hide-my-wp') ?></option>
                                            <option value="drupal" <?php selected('drupal', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Drupal 8", 'hide-my-wp') ?></option>
                                            <option value="drupal9" <?php selected('drupal9', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Drupal 9", 'hide-my-wp') ?></option>
                                            <option value="drupal10" <?php selected('drupal10', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Drupal 10", 'hide-my-wp') ?></option>
                                            <option value="drupal11" <?php selected('drupal11', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Drupal 11", 'hide-my-wp') ?></option>
                                            <option value="joomla3" <?php selected('joomla3', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Joomla 3", 'hide-my-wp') ?></option>
                                            <option value="joomla4" <?php selected('joomla4', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Joomla 4", 'hide-my-wp') ?></option>
                                            <option value="joomla5" <?php selected('joomla5', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Joomla 5", 'hide-my-wp') ?></option>
                                        </select>
                                    </div>
                                    <div class="p-1">
                                        <div class="text-black-50 small"><?php echo esc_html__( 'Simulate popular content management systems like Drupal or Joomla.', 'hide-my-wp' ); ?></div>
                                        <div class="text-black-50 small"><?php echo esc_html__( 'Deceive theme detectors and hacker bots, making them believe your website is built on Drupal or Joomla instead of WordPress.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_loggedusers" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_loggedusers" name="hmwp_hide_loggedusers" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_loggedusers' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_hide_loggedusers"><?php echo esc_html__( 'Change Paths for Logged Users', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/change-paths-for-logged-users/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Change WordPress paths while you're logged in.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_fix_relative" value="0"/>
                                            <input type="checkbox" id="hmwp_fix_relative" name="hmwp_fix_relative" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_fix_relative' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_fix_relative"><?php echo esc_html__( 'Change Relative URLs to Absolute URLs', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/change-relative-urls-to-absolute-urls/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php /* translators: 1: Site URL */ echo esc_html( sprintf( __( 'Convert links like /wp-content/* into %1$s/wp-content/*.', 'hide-my-wp' ), esc_url( site_url() ) ) ); ?></div>                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div id="hide" style="<?php echo ( $current_tab === 'hide' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__( 'Hide Options', 'hide-my-wp' ); ?></h3>
                        <?php if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) { ?>
                            <div class="card-body">
                                <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
                                    <?php /* translators: 1: Opening <a> tag to the settings page, 2: Closing </a> tag, 3: Opening <a> tag to the settings page, 4: Closing </a> tag */
                                    echo wp_kses_post( sprintf( __( 'First, you need to activate the %1$sLite Mode%2$s or %3$sGhost Mode%4$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '">', '</a>' ) ); ?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="card-body">
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_admin_toolbar" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_admin_toolbar" name="hmwp_hide_admin_toolbar" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_admin_toolbar' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_hide_admin_toolbar"><?php echo esc_html__( 'Hide Admin Toolbar', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-admin-toolbar/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( 'Hide the admin toolbar for logged users while in frontend.', 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_hide_admin_toolbar border-bottom">
                                        <div class="col-sm-4 p-1">
                                            <div class="font-weight-bold"><?php echo esc_html__( 'Select User Roles', 'hide-my-wp' ); ?>:</div>
                                            <div class="small text-black-50"><?php echo esc_html__( "User roles for who to hide the admin toolbar.", 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group">
                                            <select multiple name="hmwp_hide_admin_toolbar_roles[]" class="selectpicker form-control mb-1">
                                                <?php

                                                $selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_hide_admin_toolbar_roles' );

                                                foreach ( $allroles as $role => $name ) {
                                                    echo '<option value="' . esc_attr($role) . '" ' . ( in_array( $role, $selected_roles ) ? 'selected="selected"' : '' ) . '>' . esc_html( $name ) . '</option>';
                                                } ?>

                                            </select>
                                        </div>

                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_version" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_version" name="hmwp_hide_version" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_version' ) ? 'checked="checked"' : '' ) ?>value="1"/>
                                            <label for="hmwp_hide_version"><?php echo esc_html__( 'Hide Version from Images, CSS and JS', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-wordpress-version/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Hide all versions from the end of any Image, CSS and JavaScript files.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hide_version">
                                    <div class="checker col-sm-12 row my-2 py-0">
                                        <div class="col-sm-12 p-0 switch switch-xxs pl-5">
                                            <input type="hidden" name="hmwp_hide_version_random" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_version_random" name="hmwp_hide_version_random" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_version_random' ) ? 'checked="checked"' : '' ) ?> value="<?php echo esc_attr(wp_rand( 11111, 99999 )) ?>"/>
                                            <label for="hmwp_hide_version_random"><?php echo esc_html__( 'Random Static Number', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-wordpress-version/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                                <span class="text-black-50 small">(<?php echo esc_html__( "recommended", 'hide-my-wp' ); ?>)</span>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Add a random static number to prevent frontend caching while the user is logged in.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_styleids" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_styleids" name="hmwp_hide_styleids" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_styleids' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_hide_styleids"><?php echo esc_html__( 'Hide IDs from META Tags', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-ids-from-meta-tags/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                                <span class="text-black-50 small">(<?php echo esc_html__( "not recommended", 'hide-my-wp' ); ?>)</span>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Hide the IDs from all &lt;links&gt;, &lt;style&gt;, &lt;scripts&gt; META Tags.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 p-3 m-0 mt-1 bg-warning">
                                        <strong><?php echo esc_html__( 'Important:', 'hide-my-wp' ); ?></strong>
                                        <?php echo esc_html__( 'Hiding IDs from META tags can break caching plugins and themes that use these IDs to identify and load specific styles or scripts. Only enable this if you have tested that your site works correctly without them.', 'hide-my-wp' ); ?>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_prefetch" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_prefetch" name="hmwp_hide_prefetch" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_prefetch' ) ? 'checked="checked"' : '' ) ?>value="1"/>
                                            <label for="hmwp_hide_prefetch"><?php echo esc_html__( 'Hide WordPress DNS Prefetch META Tags', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-wordpress-dns-prefetch/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Hide the DNS Prefetch that points to WordPress.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_generator" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_generator" name="hmwp_hide_generator" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_generator' ) ? 'checked="checked"' : '' ) ?>value="1"/>
                                            <label for="hmwp_hide_generator"><?php echo esc_html__( 'Hide WordPress Generator META Tags', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-wordpress-generator/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Hide the WordPress Generator META tags.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_comments" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_comments" name="hmwp_hide_comments" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_comments' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_hide_comments"><?php echo esc_html__( 'Hide HTML Comments', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-html-comments/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Hide the HTML Comments left by the themes and plugins.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_source_map" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_source_map" name="hmwp_hide_source_map" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_hide_source_map' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_hide_source_map"><?php echo esc_html__( 'Hide Source Map References', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-source-map-references/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Removes sourceMappingURL/sourceURL hints from CSS/JS output.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_disable_emojicons" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_emojicons" name="hmwp_disable_emojicons" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_emojicons' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_emojicons"><?php echo esc_html__( 'Hide Emojicons', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-wordpress-emoji-icons/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Don't load Emoji Icons if you don't use them.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_disable_embeds" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_embeds" name="hmwp_disable_embeds" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_embeds' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_embeds"><?php echo esc_html__( 'Hide Embed Scripts', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/disable-embed-scripts/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Don't load oEmbed service if you don't use oEmbed videos.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_disable_manifest" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_manifest" name="hmwp_disable_manifest" class="switch"<?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_manifest' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_manifest"><?php echo esc_html__( 'Hide WLW Manifest Scripts', 'hide-my-wp' ); ?>
                                                <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/disable-wlw-manifest-scripts/' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Don't load WLW if you didn't configure Windows Live Writer for your site.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div id="disable" style="<?php echo ( $current_tab === 'disable' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0">
                            <?php echo esc_html__( 'Disable Options', 'hide-my-wp' ); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/disable-right-click-and-keys/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                         <div class="card-body">
                            <div class="col-sm-12 row mb-1 ml-1 p-2 border-bottom border-light">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_click" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_click" name="hmwp_disable_click" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_click' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_disable_click"><?php echo esc_html__( 'Disable Right-Click', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/disable-right-click-and-keys/#ghost-disable-right-click' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( "Disable the right-click functionality on your website.", 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_click">
                                    <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                        <?php echo esc_html__( 'Disable Click Message', 'hide-my-wp' ); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__( "Leave it blank if you don't want to display any message.", 'hide-my-wp' ); ?></div>
                                    </div>
                                    <div class="col-sm-7 p-0 input-group">
                                        <input type="text" class="form-control  mt-2" name="hmwp_disable_click_message" value="<?php echo esc_attr(HMWP_Classes_Tools::getOption( 'hmwp_disable_click_message' )) ?>"/>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_disable_click">
                                    <div class="checker col-sm-12 row my-2 py-0">
                                        <div class="col-sm-12 p-0 switch switch-xxs">
                                            <input type="hidden" name="hmwp_disable_click_loggedusers" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_click_loggedusers" name="hmwp_disable_click_loggedusers" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_click_loggedusers' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_click_loggedusers"><?php echo esc_html__( 'Disable Right-Click for Logged Users', 'hide-my-wp' ); ?></label>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_click_loggedusers">
                                        <div class="col-sm-4 p-1">
                                            <div class="font-weight-bold"><?php echo esc_html__( 'Select User Roles', 'hide-my-wp' ); ?>:</div>
                                            <div class="small text-black-50"><?php echo esc_html__( "User roles for who to disable the Right-Click.", 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group">
                                            <select multiple name="hmwp_disable_click_roles[]" class="selectpicker form-control mb-1">
                                                <?php

                                                $selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_click_roles' );

                                                foreach ( $allroles as $role => $name ) {
                                                    echo '<option value="' . esc_attr($role) . '" ' . ( in_array( $role, $selected_roles ) ? 'selected="selected"' : '' ) . '>' . esc_html( $name ) . '</option>';
                                                } ?>

                                            </select>
                                        </div>

                                    </div>

                                </div>

                            </div>
                            <div class="col-sm-12 row mb-1 ml-1 p-2 border-bottom border-light">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_inspect" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_inspect" name="hmwp_disable_inspect" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_disable_inspect"><?php echo esc_html__( 'Disable Inspect Element', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/disable-right-click-and-keys/#ghost-disable-inspect-element' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( "Disable the inspect element view on your website.", 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>


                                <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_inspect">
                                    <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                        <?php echo esc_html__( 'Disable Inspect Element Message', 'hide-my-wp' ); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__( "Leave it blank if you don't want to display any message.", 'hide-my-wp' ); ?></div>
                                    </div>
                                    <div class="col-sm-7 p-0 input-group">
                                        <input type="text" class="form-control  mt-2" name="hmwp_disable_inspect_message" value="<?php echo esc_attr(HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect_message' )) ?>"/>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_disable_inspect">

                                    <div class="checker col-sm-12 row my-2 py-1 hmwp_disable_inspect">
                                        <div class="col-sm-12 p-0 switch switch-xxs">
                                            <input type="hidden" name="hmwp_disable_inspect_blank" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_inspect_blank" name="hmwp_disable_inspect_blank" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect_blank' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_inspect_blank"><?php echo esc_html__( 'Blank Screen On Debugging', 'hide-my-wp' ); ?>
                                                <em>(<?php echo esc_html__( 'not recommended', 'hide-my-wp' ); ?>)</em></label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Show blank screen when Inspect Element is active on browser.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 p-3 m-0 mt-1 bg-warning">
                                        <strong><?php echo esc_html__( 'Important:', 'hide-my-wp' ); ?></strong>
                                        <?php echo esc_html__( 'This may not work with all new mobile devices.', 'hide-my-wp' ); ?>
                                    </div>

                                    <div class="checker col-sm-12 row my-2 py-0">
                                        <div class="col-sm-12 p-0 switch switch-xxs">
                                            <input type="hidden" name="hmwp_disable_inspect_loggedusers" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_inspect_loggedusers" name="hmwp_disable_inspect_loggedusers" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect_loggedusers' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_inspect_loggedusers"><?php echo esc_html__( 'Disable Inspect Element for Logged Users', 'hide-my-wp' ); ?></label>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_inspect_loggedusers">
                                        <div class="col-sm-4 p-1">
                                            <div class="font-weight-bold"><?php echo esc_html__( 'Select User Roles', 'hide-my-wp' ); ?>:</div>
                                            <div class="small text-black-50"><?php echo esc_html__( "User roles for who to disable the inspect element.", 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group">
                                            <select multiple name="hmwp_disable_inspect_roles[]" class="selectpicker form-control mb-1">
                                                <?php

                                                $selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect_roles' );

                                                foreach ( $allroles as $role => $name ) {
                                                    echo '<option value="' . esc_attr($role) . '" ' . ( in_array( $role, $selected_roles ) ? 'selected="selected"' : '' ) . '>' . esc_html( $name ) . '</option>';
                                                } ?>

                                            </select>
                                        </div>

                                    </div>

                                </div>

                            </div>
                            <div class="col-sm-12 row mb-1 ml-1 p-2 border-bottom border-light">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_source" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_source" name="hmwp_disable_source" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_source' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_disable_source"><?php echo esc_html__( 'Disable View Source', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/disable-right-click-and-keys/#ghost-disable-view-source' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( "Disable the source-code view on your website.", 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_source">
                                    <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                        <?php echo esc_html__( 'Disable View Source Message', 'hide-my-wp' ); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__( "Leave it blank if you don't want to display any message.", 'hide-my-wp' ); ?></div>
                                    </div>
                                    <div class="col-sm-7 p-0 input-group">
                                        <input type="text" class="form-control  mt-2" name="hmwp_disable_source_message" value="<?php echo esc_attr(HMWP_Classes_Tools::getOption( 'hmwp_disable_source_message' )) ?>"/>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_disable_source">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-xxs">
                                            <input type="hidden" name="hmwp_disable_source_loggedusers" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_source_loggedusers" name="hmwp_disable_source_loggedusers" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_source_loggedusers' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_source_loggedusers"><?php echo esc_html__( 'Disable View Source for Logged Users', 'hide-my-wp' ); ?></label>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_source_loggedusers">
                                        <div class="col-sm-4 p-1">
                                            <div class="font-weight-bold"><?php echo esc_html__( 'Select User Roles', 'hide-my-wp' ); ?>:</div>
                                            <div class="small text-black-50"><?php echo esc_html__( "User roles for who to disable the view source.", 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group">
                                            <select multiple name="hmwp_disable_source_roles[]" class="selectpicker form-control mb-1">
                                                <?php

                                                $selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_source_roles' );

                                                foreach ( $allroles as $role => $name ) {
                                                    echo '<option value="' . esc_attr($role) . '" ' . ( in_array( $role, $selected_roles ) ? 'selected="selected"' : '' ) . '>' . esc_html( $name ) . '</option>';
                                                } ?>

                                            </select>
                                        </div>

                                    </div>

                                </div>

                            </div>
                            <div class="col-sm-12 row mb-1 ml-1 p-2 border-bottom border-light">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_copy_paste" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_copy_paste" name="hmwp_disable_copy_paste" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_disable_copy_paste"><?php echo esc_html__( 'Disable Copy', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/disable-right-click-and-keys/#ghost-disable-copy-paste' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( "Disable copy function on your website.", 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_copy_paste">
                                    <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                        <?php echo esc_html__( 'Disable Copy/Paste Message', 'hide-my-wp' ); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__( "Leave it blank if you don't want to display any message.", 'hide-my-wp' ); ?></div>
                                    </div>
                                    <div class="col-sm-7 p-0 input-group">
                                        <input type="text" class="form-control  mt-2" name="hmwp_disable_copy_paste_message" value="<?php echo esc_attr(HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste_message' )) ?>"/>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_disable_copy_paste">

                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-xxs">
                                            <input type="hidden" name="hmwp_disable_paste" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_paste" name="hmwp_disable_paste" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_paste' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_paste"><?php echo esc_html__( 'Disable Paste', 'hide-my-wp' ); ?></label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__( "Disable paste function on your website.", 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>

                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-xxs">
                                            <input type="hidden" name="hmwp_disable_copy_paste_loggedusers" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_copy_paste_loggedusers" name="hmwp_disable_copy_paste_loggedusers" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste_loggedusers' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_copy_paste_loggedusers"><?php echo esc_html__( 'Disable Copy/Paste for Logged Users', 'hide-my-wp' ); ?></label>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_copy_paste_loggedusers">
                                        <div class="col-sm-4 p-1">
                                            <div class="font-weight-bold"><?php echo esc_html__( 'Select User Roles', 'hide-my-wp' ); ?>:</div>
                                            <div class="small text-black-50"><?php echo esc_html__( "User roles for who to disable the copy/paste.", 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group">
                                            <select multiple name="hmwp_disable_copy_paste_roles[]" class="selectpicker form-control mb-1">
                                                <?php

                                                $selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste_roles' );

                                                foreach ( $allroles as $role => $name ) {
                                                    echo '<option value="' . esc_attr($role) . '" ' . ( in_array( $role, $selected_roles ) ? 'selected="selected"' : '' ) . '>' . esc_html( $name ) . '</option>';
                                                } ?>

                                            </select>
                                        </div>

                                    </div>

                                </div>

                            </div>
                            <div class="col-sm-12 row mb-1 ml-1 p-2 border-bottom border-light">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_drag_drop" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_drag_drop" name="hmwp_disable_drag_drop" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_disable_drag_drop"><?php echo esc_html__( 'Disable Drag/Drop Images', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/disable-right-click-and-keys/#ghost-disable-drag-drop' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( "Disable image drag & drop on your website.", 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_drag_drop">
                                    <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                        <?php echo esc_html__( 'Disable Drag/Drop Message', 'hide-my-wp' ); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__( "Leave it blank if you don't want to display any message.", 'hide-my-wp' ); ?></div>
                                    </div>
                                    <div class="col-sm-7 p-0 input-group">
                                        <input type="text" class="form-control  mt-2" name="hmwp_disable_drag_drop_message" value="<?php echo esc_attr(HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop_message' )) ?>"/>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_disable_drag_drop">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-xxs">
                                            <input type="hidden" name="hmwp_disable_drag_drop_loggedusers" value="0"/>
                                            <input type="checkbox" id="hmwp_disable_drag_drop_loggedusers" name="hmwp_disable_drag_drop_loggedusers" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop_loggedusers' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmwp_disable_drag_drop_loggedusers"><?php echo esc_html__( 'Disable Drag/Drop for Logged Users', 'hide-my-wp' ); ?></label>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_drag_drop_loggedusers">
                                        <div class="col-sm-4 p-1">
                                            <div class="font-weight-bold"><?php echo esc_html__( 'Select User Roles', 'hide-my-wp' ); ?>:</div>
                                            <div class="small text-black-50"><?php echo esc_html__( "User roles for who to disable the drag/drop.", 'hide-my-wp' ); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group">
                                            <select multiple name="hmwp_disable_drag_drop_roles[]" class="selectpicker form-control mb-1">
                                                <?php

                                                $selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop_roles' );

                                                foreach ( $allroles as $role => $name ) {
                                                    echo '<option value="' . esc_attr($role) . '" ' . ( in_array( $role, $selected_roles ) ? 'selected="selected"' : '' ) . '>' . esc_html( $name ) . '</option>';
                                                } ?>

                                            </select>
                                        </div>

                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div id="login_design" style="<?php echo ( $current_tab === 'login_design' ? '' : 'display:none;' ); ?>" class="col-sm-12 p-0 m-0 tab-panel">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0">
                            <?php echo esc_html__('Login Page Design', 'hide-my-wp'); ?>
                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/login-page-design-customization/' ) ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_login_page" value="0"/>
                                        <input type="checkbox" id="hmwp_login_page" name="hmwp_login_page" class="switch" <?php echo( HMWP_Classes_Tools::getOption( 'hmwp_login_page' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmwp_login_page"><?php echo esc_html__( 'Use Login Page Design', 'hide-my-wp' ); ?>
                                            <a href="<?php echo esc_url( HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/login-page-design-customization/#ghost-how-to-activate' ) ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__( 'Customize your login page design while keeping it aligned with your secured login path and branding.', 'hide-my-wp' ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="hmwp_login_page">
                                <div class="col-sm-12 row py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-0 font-weight-bold" style="font-size: 0.9rem">
                                        <?php echo esc_html__('Custom Logo', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__('URL of the logo image shown on the login page. Leave empty to use the WordPress default.', 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0">
                                        <div class="input-group">
                                            <input type="text" id="hmwp_login_page_logo" class="form-control" name="hmwp_login_page_logo" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption('hmwp_login_page_logo') ); ?>" placeholder="https://example.com/logo.png" />
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="hmwp_select_logo">
                                                    <?php echo esc_html__( 'Select Image', 'hide-my-wp' ); ?>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" id="hmwp_remove_logo">
                                                    <?php echo esc_html__( 'Remove', 'hide-my-wp' ); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <div id="hmwp_logo_preview" class="mt-2" style="display:none;">
                                            <img id="hmwp_logo_preview_img" src="" alt="<?php echo esc_attr__( 'Logo preview', 'hide-my-wp' ); ?>" style="max-height:80px;max-width:200px;border:1px solid #ddd;padding:4px;background:#fff;" />
                                            <div id="hmwp_logo_preview_error" class="small text-danger mt-1" style="display:none;"><?php echo esc_html__( 'Image could not be loaded. Please check the URL.', 'hide-my-wp' ); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-0 font-weight-bold" style="font-size: 0.9rem">
                                        <?php echo esc_html__('Logo Link URL', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__('URL the logo links to. Leave empty to use the site URL.', 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <input type="text" class="form-control" name="hmwp_login_page_logo_url" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption('hmwp_login_page_logo_url') ) ?>" placeholder="<?php echo esc_attr( home_url() ); ?>" />
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-0 font-weight-bold" style="font-size: 0.9rem">
                                        <?php echo esc_html__('Background Image', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50">
                                            <?php echo esc_html__('URL of the background image shown on the login page.', 'hide-my-wp'); ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-8 p-0">
                                        <div class="input-group">
                                            <input type="text" id="hmwp_login_page_bg_image" class="form-control" name="hmwp_login_page_bg_image" value="<?php echo esc_attr( HMWP_Classes_Tools::getOption('hmwp_login_page_bg_image') ); ?>" placeholder="https://example.com/background.jpg" />
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="hmwp_select_bg">
                                                    <?php echo esc_html__( 'Select Image', 'hide-my-wp' ); ?>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" id="hmwp_remove_bg">
                                                    <?php echo esc_html__( 'Remove', 'hide-my-wp' ); ?>
                                                </button>
                                            </div>
                                        </div>

                                        <div id="hmwp_bg_preview" class="mt-2" style="display:none;">
                                            <div style="position:relative;width:100%;max-width:420px;height:180px;border:1px solid #ddd;border-radius:6px;overflow:hidden;background:#f6f7f7;">
                                                <img id="hmwp_bg_preview_img" src="" alt="<?php echo esc_attr__( 'Background preview', 'hide-my-wp' ); ?>" style="width:100%;height:100%;object-fit:cover;display:none;" />

                                                <div style="position:absolute;left:18px;top:22px;width:140px;height:110px;background:rgba(255,255,255,0.88);border:1px solid rgba(0,0,0,0.08);border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,0.08);">
                                                    <div style="height:18px;width:70px;background:#d9dde3;border-radius:3px;margin:12px auto 10px;"></div>
                                                    <div style="height:10px;width:92px;background:#e7eaee;border-radius:2px;margin:0 auto 8px;"></div>
                                                    <div style="height:10px;width:92px;background:#e7eaee;border-radius:2px;margin:0 auto 12px;"></div>
                                                    <div style="height:20px;width:92px;background:#2271b1;border-radius:3px;margin:0 auto;"></div>
                                                </div>
                                            </div>

                                            <div id="hmwp_bg_preview_error"
                                                 class="small text-danger mt-1"
                                                 style="display:none;">
                                                <?php echo esc_html__( 'Image could not be loaded. Please check the URL.', 'hide-my-wp' ); ?>
                                            </div>

                                            <div class="small text-black-50 mt-1">
                                                <?php echo esc_html__( 'Preview example. The selected layout preset may show the background differently.', 'hide-my-wp' ); ?>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Background Overlay', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__('Improve text visibility over background images.', 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select name="hmwp_login_page_bg_overlay" class="form-control">
                                            <option value="none" <?php selected('none', HMWP_Classes_Tools::getOption('hmwp_login_page_bg_overlay')); ?>><?php echo esc_html__('None', 'hide-my-wp'); ?></option>
                                            <option value="light" <?php selected('light', HMWP_Classes_Tools::getOption('hmwp_login_page_bg_overlay')); ?>><?php echo esc_html__('Light', 'hide-my-wp'); ?></option>
                                            <option value="medium" <?php selected('medium', HMWP_Classes_Tools::getOption('hmwp_login_page_bg_overlay')); ?>><?php echo esc_html__('Medium', 'hide-my-wp'); ?></option>
                                            <option value="dark" <?php selected('dark', HMWP_Classes_Tools::getOption('hmwp_login_page_bg_overlay')); ?>><?php echo esc_html__('Dark', 'hide-my-wp'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Background Blur', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__('Apply blur on the login background.', 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select name="hmwp_login_page_bg_blur" class="form-control">
                                            <option value="0" <?php selected('0', HMWP_Classes_Tools::getOption('hmwp_login_page_bg_blur')); ?>><?php echo esc_html__('None', 'hide-my-wp'); ?></option>
                                            <option value="4" <?php selected('4', HMWP_Classes_Tools::getOption('hmwp_login_page_bg_blur')); ?>>4px</option>
                                            <option value="8" <?php selected('8', HMWP_Classes_Tools::getOption('hmwp_login_page_bg_blur')); ?>>8px</option>
                                        </select>
                                    </div>
                                </div>

                                <script>
                                    jQuery(function($) {

                                        function bindImageField(args) {
                                            var input        = document.getElementById(args.inputId);
                                            var previewWrap  = document.getElementById(args.previewWrapId);
                                            var previewImg   = document.getElementById(args.previewImgId);
                                            var previewError = document.getElementById(args.previewErrorId);
                                            var selectBtn    = document.getElementById(args.selectBtnId);
                                            var removeBtn    = document.getElementById(args.removeBtnId);
                                            var timer        = null;
                                            var frame        = null;

                                            if (!input || !previewWrap || !previewImg || !previewError) {
                                                return;
                                            }

                                            function loadPreview(url) {
                                                url = (url || '').trim();

                                                if (!url) {
                                                    previewWrap.style.display = 'none';
                                                    previewImg.style.display = 'none';
                                                    previewError.style.display = 'none';
                                                    previewImg.removeAttribute('src');
                                                    return;
                                                }

                                                previewWrap.style.display = 'block';
                                                previewImg.style.display = 'none';
                                                previewError.style.display = 'none';

                                                previewImg.onload = function() {
                                                    previewImg.style.display = '';
                                                    previewError.style.display = 'none';
                                                };

                                                previewImg.onerror = function() {
                                                    previewImg.style.display = 'none';
                                                    previewError.style.display = '';
                                                };

                                                previewImg.src = url;
                                            }

                                            loadPreview(input.value);

                                            input.addEventListener('input', function() {
                                                clearTimeout(timer);
                                                timer = setTimeout(function() {
                                                    loadPreview(input.value);
                                                }, 600);
                                            });

                                            if (selectBtn) {
                                                selectBtn.addEventListener('click', function(e) {
                                                    e.preventDefault();

                                                    if (typeof wp === 'undefined' || !wp.media) {
                                                        alert(args.mediaUnavailableText);
                                                        return;
                                                    }

                                                    if (frame) {
                                                        frame.open();
                                                        return;
                                                    }

                                                    frame = wp.media({
                                                        title: args.title,
                                                        button: {
                                                            text: args.buttonText
                                                        },
                                                        library: {
                                                            type: 'image'
                                                        },
                                                        multiple: false
                                                    });

                                                    frame.on('select', function() {
                                                        var attachment = frame.state().get('selection').first().toJSON();
                                                        if (attachment && attachment.url) {
                                                            input.value = attachment.url;
                                                            loadPreview(attachment.url);
                                                        }
                                                    });

                                                    frame.open();
                                                });
                                            }

                                            if (removeBtn) {
                                                removeBtn.addEventListener('click', function(e) {
                                                    e.preventDefault();
                                                    input.value = '';
                                                    loadPreview('');
                                                });
                                            }
                                        }

                                        bindImageField({
                                            inputId: 'hmwp_login_page_logo',
                                            previewWrapId: 'hmwp_logo_preview',
                                            previewImgId: 'hmwp_logo_preview_img',
                                            previewErrorId: 'hmwp_logo_preview_error',
                                            selectBtnId: 'hmwp_select_logo',
                                            removeBtnId: 'hmwp_remove_logo',
                                            title: '<?php echo esc_js( __( 'Select Logo Image', 'hide-my-wp' ) ); ?>',
                                            buttonText: '<?php echo esc_js( __( 'Use This Logo', 'hide-my-wp' ) ); ?>',
                                            mediaUnavailableText: '<?php echo esc_js( __( 'The WordPress Media Library is not available on this page.', 'hide-my-wp' ) ); ?>'
                                        });

                                        bindImageField({
                                            inputId: 'hmwp_login_page_bg_image',
                                            previewWrapId: 'hmwp_bg_preview',
                                            previewImgId: 'hmwp_bg_preview_img',
                                            previewErrorId: 'hmwp_bg_preview_error',
                                            selectBtnId: 'hmwp_select_bg',
                                            removeBtnId: 'hmwp_remove_bg',
                                            title: '<?php echo esc_js( __( 'Select Background Image', 'hide-my-wp' ) ); ?>',
                                            buttonText: '<?php echo esc_js( __( 'Use This Background', 'hide-my-wp' ) ); ?>',
                                            mediaUnavailableText: '<?php echo esc_js( __( 'The WordPress Media Library is not available on this page.', 'hide-my-wp' ) ); ?>'
                                        });

                                    });
                                </script>

                                <?php
                                $hmwp_login_colors = array(
                                        'hmwp_login_page_bg_color'      => array( __( 'Page Background Color', 'hide-my-wp' ),  __( 'Background color of the login page.', 'hide-my-wp' ),         '#ffffff' ),
                                        'hmwp_login_page_form_bg_color' => array( __( 'Form Background Color', 'hide-my-wp' ),  __( 'Background color of the login form box.', 'hide-my-wp' ),    '#ffffff' ),
                                        'hmwp_login_page_btn_color'     => array( __( 'Button Color', 'hide-my-wp' ),           __( 'Background color of the submit button.', 'hide-my-wp' ),     '#ffffff' ),
                                        'hmwp_login_page_text_color'    => array( __( 'Text Color', 'hide-my-wp' ),             __( 'Color of labels and text on the login page.', 'hide-my-wp' ), '#ffffff' ),
                                        'hmwp_login_page_link_color'    => array( __( 'Link Color', 'hide-my-wp' ),             __( 'Color of link text on the login page.', 'hide-my-wp' ), '#ffffff' ),
                                );
                                $hmwp_login_color_keys = array_keys( $hmwp_login_colors );
                                $hmwp_login_last_key   = end( $hmwp_login_color_keys );
                                foreach ( $hmwp_login_colors as $hmwp_opt_key => $hmwp_opt_info ) :
                                    $hmwp_saved  = ( HMWP_Classes_Tools::getOption( $hmwp_opt_key ) ? '#' . HMWP_Classes_Tools::getOption( $hmwp_opt_key ) : '' );
                                    $hmwp_border = ( $hmwp_opt_key !== $hmwp_login_last_key ) ? 'border-bottom border-light' : '';
                                    ?>
                                    <div class="col-sm-12 row <?php echo esc_attr( $hmwp_border ); ?> py-3 mx-0 my-3">
                                        <div class="col-sm-4 p-0 font-weight-bold" style="font-size: 0.9rem">
                                            <?php echo esc_html( $hmwp_opt_info[0] ); ?>:
                                            <div class="small text-black-50"><?php echo esc_html( $hmwp_opt_info[1] ); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 d-flex align-items-center">
                                            <input type="color" id="<?php echo esc_attr( $hmwp_opt_key ); ?>_picker" value="<?php echo esc_attr( $hmwp_saved ?: $hmwp_opt_info[2] ); ?>" oninput="document.getElementById('<?php echo esc_attr( $hmwp_opt_key ); ?>').value=this.value" style="width:44px;height:36px;padding:2px;cursor:pointer;border:none;border-radius:4px;">
                                            <input type="text" name="<?php echo esc_attr( $hmwp_opt_key ); ?>" id="<?php echo esc_attr( $hmwp_opt_key ); ?>" class="form-control ml-2" style="max-width:120px;" value="<?php echo esc_attr( $hmwp_saved ); ?>" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)){document.getElementById('<?php echo esc_attr( $hmwp_opt_key ); ?>_picker').value=this.value}">
                                            <button type="button" class="btn btn-link btn-sm ml-2 text-danger p-0" onclick="document.getElementById('<?php echo esc_attr( $hmwp_opt_key ); ?>').value='';document.getElementById('<?php echo esc_attr( $hmwp_opt_key ); ?>_picker').value='<?php echo esc_js( $hmwp_opt_info[2] ); ?>'">
                                                <?php echo esc_html__( 'Clear', 'hide-my-wp' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="col-sm-12 p-3 m-0 mt-1 bg-warning">
                                    <strong>Note:</strong>
                                    You can make colors transparent by adding 2 extra characters at the end of the HEX code.
                                    <br><br>
                                    #FFFFFF for a solid color
                                    <br>
                                    #FFFFFFCC for a slightly transparent
                                    <br>
                                    #FFFFFF80 for a 50% transparent
                                    <br>
                                    #FFFFFF33 for a very transparent
                                </div>

                                <?php
                                $hmwp_login_presets = array(
                                        array( 'label' => 'Polar Mist',     'bg' => '#f6f8fb', 'form' => '#ffffff', 'btn' => '#2f6fed', 'text' => '#152033', 'link' => '#2f6fed' ),
                                        array( 'label' => 'Deep Horizon',   'bg' => '#101826', 'form' => '#172133', 'btn' => '#3b82f6', 'text' => '#dbe4f0', 'link' => '#7fb0ff' ),
                                        array( 'label' => 'Soft Bloom',     'bg' => '#fff7fa', 'form' => '#ffffff', 'btn' => '#d94688', 'text' => '#4a5565', 'link' => '#c83775' ),
                                        array( 'label' => 'Clear Sky',      'bg' => '#eef7ff', 'form' => '#ffffff', 'btn' => '#0891d1', 'text' => '#1b2a3a', 'link' => '#0b78b6' ),
                                        array( 'label' => 'Slate Form',     'bg' => '#f4f5f7', 'form' => '#ffffff', 'btn' => '#475569', 'text' => '#121926', 'link' => '#56657a' ),
                                        array( 'label' => 'Violet Field',   'bg' => '#f7f4ff', 'form' => '#ffffff', 'btn' => '#6d4aff', 'text' => '#202838', 'link' => '#6d4aff' ),
                                        array( 'label' => 'Evergreen',      'bg' => '#edf9f3', 'form' => '#ffffff', 'btn' => '#0f9f6e', 'text' => '#1d2a24', 'link' => '#0d8a5f' ),
                                        array( 'label' => 'Night Frame',    'bg' => '#0d1320', 'form' => '#141c2b', 'btn' => '#28b463', 'text' => '#d3dbe7', 'link' => '#8db8ff' ),
                                        array( 'label' => 'Warm Glow',      'bg' => '#fff8ef', 'form' => '#ffffff', 'btn' => '#f26b5b', 'text' => '#394150', 'link' => '#ea7a1f' ),
                                        array( 'label' => 'WordPress Classic',  'bg' => '#f1f2f4', 'form' => '#ffffff', 'btn' => '#2b77b5', 'text' => '#1f252c', 'link' => '#2b77b5' ),
                                );

                                $hmwp_layout_presets = array(
                                        array( 'value' => 'classic_card',      'label' => 'Classic Panel',      'desc' => 'A clean centered login panel',                    'group' => 'Clean' ),
                                        array( 'value' => 'corporate_panel',   'label' => 'Framed Panel',        'desc' => 'A centered panel over a structured background', 'group' => 'Clean' ),
                                        array( 'value' => 'glass_card',        'label' => 'Frosted Panel',       'desc' => 'A translucent login panel',                     'group' => 'Clean' ),

                                        array( 'value' => 'soft_split',        'label' => 'Balanced Split',      'desc' => 'Form and image on the left',         'group' => 'Split' ),
                                        array( 'value' => 'bold_split',        'label' => 'Feature Split',       'desc' => 'A stronger split-screen layout',               'group' => 'Split' ),
                                        array( 'value' => 'tinted_split',      'label' => 'Tinted Split View',   'desc' => 'Split layout with a color-tinted media side',  'group' => 'Split' ),
                                        array( 'value' => 'image_left',        'label' => 'Media Left',          'desc' => 'Media area on the left, form on the right',   'group' => 'Split' ),
                                        array( 'value' => 'image_right',       'label' => 'Media Right',         'desc' => 'Form and image on the right',   'group' => 'Split' ),
                                        array( 'value' => 'showcase_split',    'label' => 'Showcase View',       'desc' => 'Logo on the left, form on the right',      'group' => 'Split' ),

                                        array( 'value' => 'center_focus',      'label' => 'Focus Layout',        'desc' => 'Centered form with large buttons',           'group' => 'Overlay' ),
                                        array( 'value' => 'minimal_bg',        'label' => 'Minimal Overlay',     'desc' => 'A transparent form over a background image',      'group' => 'Overlay' ),
                                        array( 'value' => 'floating_left',     'label' => 'Offset Left',         'desc' => 'A floating form aligned to the left',         'group' => 'Overlay' ),
                                        array( 'value' => 'floating_right',    'label' => 'Offset Right',        'desc' => 'A floating form aligned to the right',        'group' => 'Overlay' ),
                                );

                                $current_layout = 'classic_card';
                                ?>

                                <div class="col-sm-12 py-3 mx-0 mt-2 border-top">
                                    <div class="font-weight-bold mb-1" style="font-size:1rem">
                                        <?php echo esc_html__( 'Design Presets', 'hide-my-wp' ); ?>
                                    </div>
                                    <div class="text-black-50 mb-3">
                                        <?php echo esc_html__( 'Choose a layout first, then choose the colors.', 'hide-my-wp' ); ?>
                                    </div>

                                    <style>
                                        .hmwp-preset-section-title{
                                            font-size: 0.9rem;
                                            font-weight: 700;
                                            margin: 18px 0 10px;
                                            color: #1d2327;
                                        }

                                        .hmwp-preset-grid{
                                            display:grid;
                                            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                                            gap:12px;
                                        }

                                        .hmwp-preset-tile{
                                            position:relative;
                                            border:1px solid #dcdcde;
                                            background:#fff;
                                            border-radius:8px;
                                            cursor:pointer;
                                            transition:all .15s ease;
                                            overflow:hidden;
                                        }

                                        .hmwp-preset-tile:hover{
                                            border-color:#2271b1;
                                            box-shadow:0 2px 10px rgba(0,0,0,.06);
                                            transform:translateY(-1px);
                                        }

                                        .hmwp-preset-tile.is-selected{
                                            border-color:#2271b1;
                                            box-shadow: inset 0 0 0 1px #2271b1, 0 2px 12px rgba(34,113,177,.12);
                                            background:#f6fbff;
                                        }

                                        .hmwp-preset-tile.is-selected:after{
                                            content:"✓";
                                            position:absolute;
                                            top:8px;
                                            right:10px;
                                            width:20px;
                                            height:20px;
                                            border-radius:50%;
                                            background:#2271b1;
                                            color:#fff;
                                            font-size:12px;
                                            font-weight:700;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                        }

                                        .hmwp-color-preview{
                                            display:flex;
                                            height:36px;
                                            border-bottom:1px solid #eef0f2;
                                        }

                                        .hmwp-color-preview span{
                                            flex:1;
                                        }

                                        .hmwp-color-preview .bg{ min-width:40%; }
                                        .hmwp-color-preview .form{ min-width:30%; }
                                        .hmwp-color-preview .btn{ min-width:30%; }

                                        .hmwp-preset-label{
                                            padding:10px 12px 12px;
                                            font-weight:600;
                                            color:#1d2327;
                                            font-size:14px;
                                            line-height:1.3;
                                        }

                                        .hmwp-layout-preview{
                                            height:78px;
                                            background:#f6f7f7;
                                            border-bottom:1px solid #eef0f2;
                                            position:relative;
                                        }

                                        .hmwp-layout-preview .frame{
                                            position:absolute;
                                            inset:10px;
                                            border:1px solid #d5d9dd;
                                            background:#fff;
                                            overflow:hidden;
                                        }

                                        .hmwp-layout-preview .panel,
                                        .hmwp-layout-preview .image,
                                        .hmwp-layout-preview .card,
                                        .hmwp-layout-preview .topband{
                                            position:absolute;
                                        }

                                        .hmwp-layout-preview .card{
                                            width: 30%;
                                            height: 60%;
                                            left: 7%;
                                            right: auto;
                                            top: 22%;
                                            margin: 0;
                                        }

                                        .hmwp-layout-preview.classic_card .card{
                                            width:40%;
                                            height:52%;
                                            left:30%;
                                            top:24%;
                                            background:#fff;
                                            border:1px solid #cfd6dc;
                                            border-radius:4px;
                                        }

                                        .hmwp-layout-preview.corporate_panel .topband{
                                            left:0; right:0; top:0; height:35%;
                                            background:#243670;
                                        }
                                        .hmwp-layout-preview.corporate_panel .card{
                                            width:42%;
                                            height:60%;
                                            left:29%;
                                            top:20%;
                                            background:#fff;
                                            border:1px solid #cfd6dc;
                                            border-radius:4px;
                                        }

                                        .hmwp-layout-preview.glass_card .card{
                                            width:42%;
                                            height:54%;
                                            left:29%;
                                            top:22%;
                                            background:rgba(255,255,255,.7);
                                            border:1px solid #cfd6dc;
                                            border-radius:4px;
                                        }

                                        .hmwp-layout-preview.soft_split .panel,
                                        .hmwp-layout-preview.bold_split .panel,
                                        .hmwp-layout-preview.tinted_split .panel,
                                        .hmwp-layout-preview.image_right .panel,
                                        .hmwp-layout-preview.showcase_split .panel{
                                            left:0; top:0; bottom:0; width:44%;
                                            background:#fff;
                                        }
                                        .hmwp-layout-preview.bold_split .image,
                                        .hmwp-layout-preview.tinted_split .image,
                                        .hmwp-layout-preview.image_right .image{
                                            right:0; top:0; bottom:0; width:56%;
                                            background:#dbe7f3;
                                        }

                                        .hmwp-layout-preview.showcase_split .image{
                                            left: 25px;
                                            top: 30%;
                                            bottom: 0;
                                            width: 45%;
                                            height: 45%;
                                        }

                                        .hmwp-layout-preview.bold_split .image{ background:#c8d9ed; }
                                        .hmwp-layout-preview.tinted_split .image{ background:linear-gradient(135deg,#9b8cf2,#d7b6ff); }
                                        .hmwp-layout-preview.showcase_split .image{ background:#cad8e8; }

                                        .hmwp-layout-preview.soft_split .image,
                                        .hmwp-layout-preview.image_left .image{
                                            left:0; top:0; bottom:0; width:56%;
                                            background:#dbe7f3;
                                        }
                                        .hmwp-layout-preview.image_left .panel{
                                            right:0; top:0; bottom:0; width:44%;
                                            background:#fff;
                                        }

                                        .hmwp-layout-preview.image_left .card,
                                        .hmwp-layout-preview.image_right .card,
                                        .hmwp-layout-preview.showcase_split .card{
                                            right: 6%;
                                            left: auto;
                                        }

                                        .hmwp-layout-preview.center_focus .card,
                                        .hmwp-layout-preview.minimal_bg .card{
                                            width:38%;
                                            height:42%;
                                            left:31%;
                                            top:29%;
                                            background:transparent;
                                            border:1px dashed #bcc6d0;
                                            border-radius:4px;
                                        }

                                        .hmwp-layout-preview.floating_left .card{
                                            width:32%;
                                            height:44%;
                                            left:11%;
                                            top:28%;
                                            background:transparent;
                                            border:1px dashed #bcc6d0;
                                        }

                                        .hmwp-layout-preview.floating_right .card{
                                            width:32%;
                                            height:44%;
                                            right:11%;
                                            top:28%;
                                            background:transparent;
                                            border:1px dashed #bcc6d0;
                                        }

                                        .hmwp-layout-name{
                                            padding:10px 12px;
                                            font-weight:600;
                                            color:#1d2327;
                                            font-size:14px;
                                            line-height:1.3;
                                            min-height:46px;
                                            display:flex;
                                            align-items:center;
                                        }

                                        .hmwp-layout-help{
                                            margin-top:10px;
                                            padding:10px 12px;
                                            background:#f6f7f7;
                                            border:1px solid #dcdcde;
                                            border-radius:6px;
                                            font-size:13px;
                                            color:#50575e;
                                        }
                                    </style>

                                    <div class="col-sm-12 p-0 mx-0 my-3 <?php echo esc_attr(HMWP_CLASS_CTA) ?>">

                                        <div class="col-sm-12 font-weight-bold p-0 m-0 my-2">
                                            <?php echo esc_html__( '1. Layout Presets', 'hide-my-wp' ); ?>
                                        </div>

                                        <input type="hidden" name="hmwp_login_page_layout" id="hmwp_login_page_layout" value="<?php echo esc_attr( $current_layout ); ?>" />

                                        <div class="col-sm-12 row p-0 p-2 mx-0 my-3 hmwp-preset-grid" id="hmwp-layout-grid">
                                            <?php foreach ( $hmwp_layout_presets as $preset ) :
                                                $selected = ( $current_layout === $preset['value'] ) ? 'is-selected' : '';
                                                ?>
                                                <button type="button"
                                                        class="hmwp-preset-tile hmwp-layout-tile <?php echo esc_attr( $selected ); ?>"
                                                        data-layout="<?php echo esc_attr( $preset['value'] ); ?>"
                                                        data-desc="<?php echo esc_attr( $preset['desc'] ); ?>"
                                                        aria-pressed="<?php echo $selected ? 'true' : 'false'; ?>">
                                                    <div class="hmwp-layout-preview <?php echo esc_attr( $preset['value'] ); ?>">
                                                        <div class="frame">
                                                            <div class="topband"></div>
                                                            <div class="panel"></div>
                                                            <div class="image"></div>
                                                            <div class="card"></div>
                                                        </div>
                                                    </div>
                                                    <div class="hmwp-layout-name"><?php echo esc_html( $preset['label'] ); ?></div>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="col-sm-12 p-2 m-0 my-2 hmwp-layout-help" id="hmwp-layout-help">
                                            <?php
                                            $selected_desc = '';
                                            foreach ( $hmwp_layout_presets as $preset ) {
                                                if ( $preset['value'] === $current_layout ) {
                                                    $selected_desc = $preset['desc'];
                                                    break;
                                                }
                                            }
                                            echo esc_html( $selected_desc ?: __( 'Choose the login page structure.', 'hide-my-wp' ) );
                                            ?>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 row p-0 mx-0 my-3 ">

                                        <div class="col-sm-12 font-weight-bold p-0 m-0 my-2">
                                            <?php echo esc_html__( '2. Color Presets', 'hide-my-wp' ); ?>
                                        </div>

                                        <div class="col-sm-12 row p-0 p-2 mx-0 my-3 hmwp-preset-grid" id="hmwp-color-grid">
                                            <?php foreach ( $hmwp_login_presets as $preset ) :
                                                $preset_data = wp_json_encode( array(
                                                        'hmwp_login_page_bg_color'      => ltrim( $preset['bg'], '#' ),
                                                        'hmwp_login_page_form_bg_color' => ltrim( $preset['form'], '#' ),
                                                        'hmwp_login_page_btn_color'     => ltrim( $preset['btn'], '#' ),
                                                        'hmwp_login_page_text_color'    => ltrim( $preset['text'], '#' ),
                                                        'hmwp_login_page_link_color'    => ltrim( $preset['link'], '#' ),
                                                ) );
                                                ?>
                                                <button type="button" class="hmwp-preset-tile hmwp-color-tile" data-preset="<?php echo esc_attr( $preset_data ); ?>">
                                                    <div class="hmwp-color-preview">
                                                        <span class="bg"   style="background:<?php echo esc_attr( $preset['bg'] ); ?>"></span>
                                                        <span class="form" style="background:<?php echo esc_attr( $preset['form'] ); ?>"></span>
                                                        <span class="btn"  style="background:<?php echo esc_attr( $preset['btn'] ); ?>"></span>
                                                    </div>
                                                    <div class="hmwp-preset-label"><?php echo esc_html( $preset['label'] ); ?></div>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>

                                        <script>
                                            (function(){
                                                var layoutInput = document.getElementById('hmwp_login_page_layout');
                                                var layoutHelp  = document.getElementById('hmwp-layout-help');

                                                document.querySelectorAll('.hmwp-layout-tile').forEach(function(tile){
                                                    tile.addEventListener('click', function(){
                                                        layoutInput.value = this.dataset.layout;

                                                        document.querySelectorAll('.hmwp-layout-tile').forEach(function(el){
                                                            el.classList.remove('is-selected');
                                                            el.setAttribute('aria-pressed', 'false');
                                                        });

                                                        this.classList.add('is-selected');
                                                        this.setAttribute('aria-pressed', 'true');

                                                        if (layoutHelp && this.dataset.desc) {
                                                            layoutHelp.textContent = this.dataset.desc;
                                                        }
                                                    });
                                                });

                                                document.querySelectorAll('.hmwp-color-tile').forEach(function(tile) {
                                                    tile.addEventListener('click', function() {
                                                        var preset = JSON.parse(this.dataset.preset);

                                                        Object.keys(preset).forEach(function(key) {
                                                            var val    = '#' + preset[key];
                                                            var input  = document.getElementById(key);
                                                            var picker = document.getElementById(key + '_picker');

                                                            if (input) {
                                                                input.value = val;
                                                            }
                                                            if (picker) {
                                                                picker.value = val;
                                                            }
                                                        });

                                                        document.querySelectorAll('.hmwp-color-tile').forEach(function(el){
                                                            el.classList.remove('is-selected');
                                                        });
                                                        this.classList.add('is-selected');
                                                    });
                                                });
                                            })();
                                        </script>

                                    </div>
                                </div>

                                <script>
                                    document.querySelectorAll('.hmwp-login-preset').forEach(function(btn) {
                                        btn.addEventListener('click', function() {
                                            var preset = JSON.parse(this.dataset.preset);
                                            Object.keys(preset).forEach(function(key) {
                                                var val    = '#' + preset[key];
                                                var input  = document.getElementById(key);
                                                var picker = document.getElementById(key + '_picker');
                                                if (input)  { input.value  = val; }
                                                if (picker) { picker.value = val; }
                                            });
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                </div>

                <?php do_action( 'hmwp_tweaks_form_end' ) ?>

                 <div class="col-sm-12 m-0 p-2 card-footer save-button text-center">
                    <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__( 'Save', 'hide-my-wp' ); ?></button>
                </div>
            </form>
        </div>

        <div class="hmwp_col hmwp_col_side p-0 pr-2 mr-2">
			<?php $view->show( 'blocks/ChangeCacheFiles' ); ?>
			<?php $view->show( 'blocks/SecurityCheck' ); ?>
        </div>

    </div>

</div>
