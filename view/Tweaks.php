<div id="hmw_wrap" class="d-flex flex-row my-3 bg-light">
	<?php echo $view->getAdminTabs( HMW_Classes_Tools::getValue( 'tab', 'hmw_permalinks' ) ); ?>
    <div class="hmw_row d-flex flex-row bg-white px-3">
        <div class="hmw_col flex-grow-1 mr-3">
            <form method="POST">
				<?php wp_nonce_field( 'hmw_tweakssettings', 'hmw_nonce' ) ?>
                <input type="hidden" name="action" value="hmw_tweakssettings"/>
                <div class="card p-0 col-sm-12 tab-panel">
                    <h3 class="card-title bg-brown text-white p-2"><?php _e( 'Redirects Settings', _HMW_PLUGIN_NAME_ ); ?>:</h3>
                    <div class="card-body">
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-1">
                                <div class="font-weight-bold"><?php _e( 'Redirect hidden paths', _HMW_PLUGIN_NAME_ ); ?>:</div>
                            </div>
                            <div class="col-sm-8 p-0 input-group-lg mb-1">
                                <select name="hmw_url_redirect" class="form-control bg-input mb-1">
                                    <option value="." <?php selected( '.', HMW_Classes_Tools::getOption( 'hmw_url_redirect' ), true ) ?>><?php _e( "Front page", _HMW_PLUGIN_NAME_ ) ?></option>
                                    <option value="404" <?php selected( '404', HMW_Classes_Tools::getOption( 'hmw_url_redirect' ), true ) ?> ><?php _e( "404 page", _HMW_PLUGIN_NAME_ ) ?></option>
                                    <option value="NFError" <?php selected( 'NFError', HMW_Classes_Tools::getOption( 'hmw_url_redirect' ), true ) ?> ><?php _e( "404 HTML Error", _HMW_PLUGIN_NAME_ ) ?></option>
									<?php
									$pages = get_pages();
									foreach ( $pages as $page ) {
										$option = '<option value="' . $page->post_name . '" ' . selected( $page->post_name, HMW_Classes_Tools::getOption( 'hmw_url_redirect' ), true ) . '>';
										$option .= $page->post_title;
										$option .= '</option>';
										echo $option;
									} ?>
                                </select>
                                <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#redirect_paths" target="_blank" class="position-absolute float-right" style="right: 27px;top: 25%;"><i class="fa fa-question-circle"></i></a>
                            </div>
                            <div class="p-1">
                                <div class="text-black-50"><?php echo __( 'Redirect the protected paths /wp-admin, /wp-login to Front Page or 404 page.', _HMW_PLUGIN_NAME_ ); ?></div>
                                <div class="text-black-50"><?php echo __( 'You can create a new page and come back to choose to redirect to that page', _HMW_PLUGIN_NAME_ ); ?></div>
                            </div>
                        </div>

						<?php
						/** @var $wp_roles WP_Roles */
						global $wp_roles;

						$allroles = array();
						if ( function_exists( 'wp_roles' ) ) {
							$allroles = wp_roles()->get_names();
							if ( ! empty( $allroles ) ) {
								$allroles = array_keys( $allroles );
							}
						}

						$urlRedirects = HMW_Classes_Tools::getOption( 'hmw_url_redirects' );
						?>
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#default" role="tab" aria-controls="default" aria-selected="true"><?php echo __( "Default", _HMW_PLUGIN_NAME_ ) ?></a>
                            </li>
							<?php if ( ! empty( $allroles ) ) { ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><?php echo __( "User Role", _HMW_PLUGIN_NAME_ ) ?></a>
                                    <div class="dropdown-menu" style="height: auto; max-height: 200px; overflow-x: hidden;">
										<?php foreach ( $allroles as $role ) { ?>
                                            <a class="dropdown-item" data-toggle="tab" href="#nav-<?php echo esc_attr( $role ) ?>" role="tab" aria-controls="nav-<?php echo esc_attr( $role ) ?>" aria-selected="false"><?php echo esc_attr( ucwords( str_replace( '_', ' ', $role ) ) ) ?></a>
										<?php } ?>
                                    </div>
                                </li>
							<?php } ?>
                        </ul>
                        <div class="tab-content border-right border-left border-bottom p-0 m-0">
                            <div class="tab-pane fade show active" id="default" role="tabpanel" aria-labelledby="nav-home-tab">

                                <div class="col-sm-12 row border-bottom border-light py-4 m-0">
                                    <div class="col-sm-4 p-0 py-2 font-weight-bold">
										<?php _e( 'Login Redirect URL', _HMW_PLUGIN_NAME_ ); ?>:
                                        <div class="small text-black-50"><?php echo __( "eg.", _HMW_PLUGIN_NAME_ ) . ' ' . admin_url( '', 'relative' ); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group input-group-lg">
                                        <input type="text" class="form-control bg-input" name="hmw_url_redirects[default][login]" value="<?php echo( isset( $urlRedirects['default']['login'] ) ? $urlRedirects['default']['login'] : '' ) ?>"/>
                                        <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#redirect_on_login" target="_blank" class="position-absolute float-right" style="right: 7px;top: 25%;"><i class="fa fa-question-circle"></i></a>
                                    </div>
                                </div>

                                <div class="col-sm-12 row border-bottom border-light py-4 mx-0">
                                    <div class="col-sm-4 p-0 py-2 font-weight-bold">
										<?php _e( 'Logout Redirect URL', _HMW_PLUGIN_NAME_ ); ?>:
                                        <div class="small text-black-50"><?php echo __( "eg. /logout or ", _HMW_PLUGIN_NAME_ ) . ' ' . home_url( '', 'relative' ); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group input-group-lg">
                                        <input type="text" class="form-control bg-input" name="hmw_url_redirects[default][logout]" value="<?php echo( isset( $urlRedirects['default']['logout'] ) ? $urlRedirects['default']['logout'] : '' ) ?>" />
                                        <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#redirect_on_login" target="_blank" class="position-absolute float-right" style="right: 7px;top: 25%;"><i class="fa fa-question-circle"></i></a>
                                    </div>
                                </div>

                                <div class="p-3">
                                    <div class="text-danger"><?php echo sprintf(__( "%s Note! %s Make sure you that the redirect URLs exist on your website. Only add local URLs.", _HMW_PLUGIN_NAME_ ),'<strong>','</strong>'); ?></div>
                                </div>
                            </div>

							<?php if ( ! empty( $allroles ) ) {
								foreach ( $allroles as $role ) { ?>
                                    <div class="tab-pane fade" id="nav-<?php echo esc_attr( $role ) ?>" role="tabpanel" aria-labelledby="nav-profile-tab">
                                        <h5 class="card-title pt-3 pb-1 mx-3 text-black-50 border-bottom border-light"><?php echo ucwords( str_replace( '_', ' ', $role ) ) . ' ' . __( "redirects", _HMW_PLUGIN_NAME_ ); ?>:</h5>
                                        <div class="col-sm-12 row border-bottom border-light py-4 m-0">
                                            <div class="col-sm-4 p-0 py-2 font-weight-bold">
												<?php _e( 'Login Redirect URL', _HMW_PLUGIN_NAME_ ); ?>:
                                                <div class="small text-black-50"><?php echo __( "eg.", _HMW_PLUGIN_NAME_ ) . ' ' . admin_url( '', 'relative' ); ?></div>
                                            </div>
                                            <div class="col-sm-8 p-0 input-group input-group-lg">
                                                <input type="text" class="form-control bg-input" name="hmw_url_redirects[<?php echo $role ?>][login]" value="<?php echo( isset( $urlRedirects[ $role ]['login'] ) ? $urlRedirects[ $role ]['login'] : '' ) ?>"/>
                                                <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#redirect_on_login" target="_blank" class="position-absolute float-right" style="right: 7px;top: 25%;"><i class="fa fa-question-circle"></i></a>
                                            </div>
                                        </div>

                                        <div class="col-sm-12 row border-bottom border-light py-4 m-0">
                                            <div class="col-sm-4 p-0 py-2 font-weight-bold">
												<?php _e( 'Logout Redirect URL', _HMW_PLUGIN_NAME_ ); ?>:
                                                <div class="small text-black-50"><?php echo __( "eg. /logout or ", _HMW_PLUGIN_NAME_ ) . ' ' . home_url( '', 'relative' ); ?></div>
                                            </div>
                                            <div class="col-sm-8 p-0 input-group input-group-lg">
                                                <input type="text" class="form-control bg-input" name="hmw_url_redirects[<?php echo $role ?>][logout]" value="<?php echo( isset( $urlRedirects[ $role ]['logout'] ) ? $urlRedirects[ $role ]['logout'] : '' ) ?>"/>
                                                <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#redirect_on_login" target="_blank" class="position-absolute float-right" style="right: 7px;top: 25%;"><i class="fa fa-question-circle"></i></a>
                                            </div>
                                        </div>

                                        <div class="p-3">
                                            <div class="text-danger"><?php echo sprintf(__( "%s Note! %s Make sure you that the redirect URLs exist on your website. %sThe User Role redirect URL has higher priority than the Default redirect URL.", _HMW_PLUGIN_NAME_ ),'<strong>','</strong>', '<br />'); ?></div>
                                        </div>
                                    </div>
								<?php }
							} ?>


                        </div>
                    </div>
                </div>
                <div class="card col-sm-12 p-0 tab-panel">
                    <h3 class="card-title bg-brown text-white p-2"><?php _e( 'Change Options', _HMW_PLUGIN_NAME_ ); ?>:</h3>
                    <div class="card-body">
						<?php if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'default' ) { ?>
                            <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3 text-black-50 text-center">
								<?php echo __( 'First, you need to switch Hide My Wp from Default mode to Safe Mode or Ghost Mode.', _HMW_PLUGIN_NAME_ ) ?>
                            </div>
						<?php } else { ?>
                            <div class="col-sm-12 row mb-1 ml-1">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmw_hide_loggedusers" value="0"/>
                                        <input type="checkbox" id="hmw_hide_loggedusers" name="hmw_hide_loggedusers" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_hide_loggedusers' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmw_hide_loggedusers"><?php _e( 'Change Paths for Logged Users', _HMW_PLUGIN_NAME_ ); ?></label>
                                        <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#change_paths_logged_users" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                        <div class="offset-1 text-black-50"><?php _e( "Change WordPress paths while you're logged in", _HMW_PLUGIN_NAME_ ); ?></div>
                                        <div class="offset-1 text-black-50"><?php _e( "(not recommended, may affect other plugins functionality in admin)", _HMW_PLUGIN_NAME_ ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmw_hideajax_paths" value="0"/>
                                        <input type="checkbox" id="hmw_hideajax_paths" name="hmw_hideajax_paths" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_hideajax_paths' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmw_hideajax_paths"><?php _e( 'Change Paths in Ajax Calls', _HMW_PLUGIN_NAME_ ); ?></label>
                                        <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#change_paths_ajax" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo __( 'This will prevent from showing the old paths when an image or font is called through ajax', _HMW_PLUGIN_NAME_ ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmw_fix_relative" value="0"/>
                                        <input type="checkbox" id="hmw_fix_relative" name="hmw_fix_relative" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_fix_relative' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmw_fix_relative"><?php _e( 'Change Relative URLs to Absolute URLs', _HMW_PLUGIN_NAME_ ); ?></label>
                                        <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#fix_relative_urls" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo sprintf( __( 'Convert links like /wp-content/* into  %s/wp-content/*.', _HMW_PLUGIN_NAME_ ), site_url() ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmw_shutdown_load" value="0"/>
                                        <input type="checkbox" id="hmw_shutdown_load" name="hmw_shutdown_load" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_shutdown_load' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmw_shutdown_load"><?php _e( 'Change Paths in Sitemaps XML', _HMW_PLUGIN_NAME_ ); ?></label>
                                        <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#fix_sitemap_xml" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo sprintf( __( 'Double check the Sitemap XML files and make sure the paths are changed.', _HMW_PLUGIN_NAME_ ), site_url() ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmw_robots" value="0"/>
                                        <input type="checkbox" id="hmw_robots" name="hmw_robots" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_robots' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmw_robots"><?php _e( 'Change Paths in Robots.txt', _HMW_PLUGIN_NAME_ ); ?></label>
                                        <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#fix_robots_txt" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo __( 'Hide WordPress paths from robots.txt file', _HMW_PLUGIN_NAME_ ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1">
                                <div class="box" data-toggle="popover" data-html="true" data-placement="top" data-content="<?php echo sprintf( __( 'This feature requires %sHide My WP Ghost%s.', _HMW_PLUGIN_NAME_ ), "<a href='https://hidemywpghost.com/hide-my-wp-pricing/' target='_blank'>", "</a>" ) ?>">
                                    <div class="ribbon"><span><?php echo __( 'PRO', _HMW_PLUGIN_NAME_ ) ?></span></div>
                                </div>
                                <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <div class="hmw_pro">
                                            <img src="<?php echo _HMW_THEME_URL_ . 'img/pro.png' ?>"></div>

                                        <label for="hmw_in_dashboard"><?php _e( 'Change Paths in Cached Files', _HMW_PLUGIN_NAME_ ); ?></label>
                                        <div class="offset-1 text-black-50"><?php _e( 'Change the WordPress common paths in the cached files from /wp-content/cache directory', _HMW_PLUGIN_NAME_ ); ?></div>
                                        <div class="offset-1 text-black-50"><?php _e( '(this feature runs in background and needs up to one minute after every cache purged)', _HMW_PLUGIN_NAME_ ); ?></div>
                                    </div>
                                </div>
                            </div>
						<?php } ?>
                    </div>
                </div>
                <div class="card col-sm-12 p-0 tab-panel">
                    <h3 class="card-title bg-brown text-white p-2"><?php _e( 'Hide/Show Options', _HMW_PLUGIN_NAME_ ); ?>:</h3>
                    <div class="card-body">
	                    <?php if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'default' ) { ?>
                            <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3 text-black-50 text-center">
			                    <?php echo __( 'First, you need to switch Hide My Wp from Default mode to Safe Mode or Ghost Mode.', _HMW_PLUGIN_NAME_ ) ?>
                            </div>
	                    <?php } else { ?>
                        <div class="col-sm-12 row mb-1 ml-1">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmw_hide_version" value="0"/>
                                    <input type="checkbox" id="hmw_hide_version" name="hmw_hide_version" class="js-switch pull-right fixed-sidebar-check" <?php echo( HMW_Classes_Tools::getOption( 'hmw_hide_version' ) ? 'checked="checked"' : '' ) ?>value="1"/>
                                    <label for="hmw_hide_version"><?php _e( 'Hide Versions and WordPress Tags', _HMW_PLUGIN_NAME_ ); ?></label>
                                    <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#hide_wordpress_version" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                    <div class="offset-1 text-black-50"><?php _e( "Hide WordPress and Plugin versions from the end of any image, css and js files", _HMW_PLUGIN_NAME_ ); ?></div>
                                    <div class="offset-1 text-black-50"><?php _e( "Hide the WP Generator META", _HMW_PLUGIN_NAME_ ); ?></div>
                                    <div class="offset-1 text-black-50"><?php _e( "Hide the WP DNS Prefetch META", _HMW_PLUGIN_NAME_ ); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmw_hide_header" value="0"/>
                                    <input type="checkbox" id="hmw_hide_header" name="hmw_hide_header" class="js-switch pull-right fixed-sidebar-check" <?php echo( HMW_Classes_Tools::getOption( 'hmw_hide_header' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmw_hide_header"><?php _e( 'Hide RSD (Really Simple Discovery) header', _HMW_PLUGIN_NAME_ ); ?></label>
                                    <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#hide_rsd" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                    <div class="offset-1 text-black-50"><?php _e( "Don't show any WordPress information in HTTP header request", _HMW_PLUGIN_NAME_ ); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmw_hide_comments" value="0"/>
                                    <input type="checkbox" id="hmw_hide_comments" name="hmw_hide_comments" class="js-switch pull-right fixed-sidebar-check" <?php echo( HMW_Classes_Tools::getOption( 'hmw_hide_comments' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmw_hide_comments"><?php _e( 'Hide WordPress HTML Comments', _HMW_PLUGIN_NAME_ ); ?></label>
                                    <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#hide_comments" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                    <div class="offset-1 text-black-50"><?php _e( "Hide the HTML Comments left by theme and plugins", _HMW_PLUGIN_NAME_ ); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmw_disable_emojicons" value="0"/>
                                    <input type="checkbox" id="hmw_disable_emojicons" name="hmw_disable_emojicons" class="js-switch pull-right fixed-sidebar-check" <?php echo( HMW_Classes_Tools::getOption( 'hmw_disable_emojicons' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmw_disable_emojicons"><?php _e( 'Hide Emojicons', _HMW_PLUGIN_NAME_ ); ?></label>
                                    <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#hide_emojicons" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                    <div class="offset-1 text-black-50"><?php _e( "Don't load Emoji Icons if you don't use them", _HMW_PLUGIN_NAME_ ); ?></div>
                                </div>
                            </div>
                        </div>
	                    <?php } ?>
                    </div>
                </div>
                <div class="card col-sm-12 p-0 tab-panel">
                    <h3 class="card-title bg-brown text-white p-2"><?php _e( 'Disable Options', _HMW_PLUGIN_NAME_ ); ?>:</h3>
                    <div class="card-body">
	                    <?php if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'default' ) { ?>
                            <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3 text-black-50 text-center">
			                    <?php echo __( 'First, you need to switch Hide My Wp from Default mode to Safe Mode or Ghost Mode.', _HMW_PLUGIN_NAME_ ) ?>
                            </div>
	                    <?php } else { ?>
                        <div class="col-sm-12 row mb-1 ml-1">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmw_disable_xmlrpc" value="0"/>
                                    <input type="checkbox" id="hmw_disable_xmlrpc" name="hmw_disable_xmlrpc" class="js-switch pull-right fixed-sidebar-check" <?php echo( HMW_Classes_Tools::getOption( 'hmw_disable_xmlrpc' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmw_disable_xmlrpc"><?php _e( 'Disable XML-RPC authentication', _HMW_PLUGIN_NAME_ ); ?></label>
                                    <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#disable_xml_rpc_access" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                    <div class="offset-1 text-black-50"><?php echo sprintf( __( "Don't load XML-RPC to prevent %sBrute force attacks via XML-RPC%s", _HMW_PLUGIN_NAME_ ), '<a href="https://hidemywpghost.com/should-you-disable-xml-rpc-on-wordpress/" target="_blank">', '</a>' ); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmw_disable_embeds" value="0"/>
                                    <input type="checkbox" id="hmw_disable_embeds" name="hmw_disable_embeds" class="js-switch pull-right fixed-sidebar-check" <?php echo( HMW_Classes_Tools::getOption( 'hmw_disable_embeds' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmw_disable_embeds"><?php _e( 'Disable Embed scripts', _HMW_PLUGIN_NAME_ ); ?></label>
                                    <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#disable_embed_scripts" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                    <div class="offset-1 text-black-50"><?php _e( "Don't load oEmbed service if you don't use oEmbed videos", _HMW_PLUGIN_NAME_ ); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmw_disable_manifest" value="0"/>
                                    <input type="checkbox" id="hmw_disable_manifest" name="hmw_disable_manifest" class="js-switch pull-right fixed-sidebar-check" <?php echo( HMW_Classes_Tools::getOption( 'hmw_disable_manifest' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmw_disable_manifest"><?php _e( 'Disable WLW Manifest scripts', _HMW_PLUGIN_NAME_ ); ?></label>
                                    <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#disable_wlw_scripts" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                    <div class="offset-1 text-black-50"><?php _e( "Don't load WLW if you didn't configure Windows Live Writer for your site", _HMW_PLUGIN_NAME_ ); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmw_disable_debug" value="0"/>
                                    <input type="checkbox" id="hmw_disable_debug" name="hmw_disable_debug" class="js-switch pull-right fixed-sidebar-check" <?php echo( HMW_Classes_Tools::getOption( 'hmw_disable_debug' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                    <label for="hmw_disable_debug"><?php _e( 'Disable DB Debug in Frontent', _HMW_PLUGIN_NAME_ ); ?></label>
                                    <a href="https://hidemywpghost.com/kb/activate-security-tweaks/#disable_db_debug" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                    <div class="offset-1 text-black-50"><?php _e( "Don't load DB Debug if your website is live", _HMW_PLUGIN_NAME_ ); ?></div>
                                </div>
                            </div>
                        </div>
	                    <?php } ?>
                    </div>

                </div>
                <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0px 0px 8px -3px #444;">
                    <button type="submit" class="btn rounded-0 btn-success btn-lg px-5 mr-5 save"><?php _e( 'Save', _HMW_PLUGIN_NAME_ ); ?></button>
                    <a href="https://wordpress.org/support/plugin/hide-my-wp/reviews/?rate=5#new-post" target="_blank" style="color: #ff005e;"><?php echo sprintf( __( 'Love Hide My WP %s? Show us ;)', _HMW_PLUGIN_NAME_ ), _HMW_VER_NAME_ ); ?></a>
                </div>
            </form>
        </div>
        <div class="hmw_col hmw_col_side">
			<?php echo $view->getView( 'Support' ) ?>


            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-center">
                    <h3 class="card-title"><?php _e( 'Check Your Website', _HMW_PLUGIN_NAME_ ); ?></h3>
                    <div class="card-text text-muted">
						<?php echo __( 'Check if your website is secured with the current settings.', _HMW_PLUGIN_NAME_ ) ?>
                    </div>
                    <div class="card-text text-info m-3">
                        <a href="<?php echo HMW_Classes_Tools::getSettingsUrl( 'hmw_securitycheck' ) ?>" class="btn rounded-0 btn-warning btn-lg text-white px-5 securitycheck"><?php _e( 'Security Check', _HMW_PLUGIN_NAME_ ); ?></a>
                    </div>
                    <div class="card-text text-muted small">
						<?php echo __( 'Make sure you save the settings and empty the cache before checking your website with our tool.', _HMW_PLUGIN_NAME_ ) ?>
                    </div>

                    <div class="card-text m-3 ">
                        <a class="bigbutton text-center" href="https://hidemywpghost.com/knowledge-base/" target="_blank"><?php echo __( "Learn more about Hide My WP", _HMW_PLUGIN_NAME_ ); ?></a>
                    </div>
                </div>
            </div>

            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-center">
                    <h3 class="card-title"><?php echo __( 'Love Hide My WP?', _HMW_PLUGIN_NAME_ ); ?></h3>
                    <div class="card-text text-muted">
                        <h1>
                            <a href="https://wordpress.org/support/plugin/hide-my-wp/reviews/?rate=5#new-post" target="_blank" style="font-size: 80px"><i class="fa fa-heart text-danger"></i></a>
                        </h1>
						<?php echo __( 'Please help us and support our plugin on WordPress.org', _HMW_PLUGIN_NAME_ ) ?>
                    </div>
                    <div class="card-text text-info m-3">
                        <a href="https://wordpress.org/support/plugin/hide-my-wp/reviews/?rate=5#new-post" target="_blank" class="btn rounded-0 btn-success btn-lg px-4"><?php echo __( 'Rate Hide My WP', _HMW_PLUGIN_NAME_ ); ?></a>
                    </div>
                    <div class="card-text text-muted">
						<?php echo __( 'Contact us after you left the review cause we have a surprise for you.', _HMW_PLUGIN_NAME_ ) ?>
                        <h1>
                            <a href="https://hidemywpghost.com/contact/" target="_blank" style="font-size: 80px"><i class="fa fa-gift text-info"></i></a>
                        </h1>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>