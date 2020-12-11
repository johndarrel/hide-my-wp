<?php if ( HMW_Classes_Tools::isPermalinkStructure() ) { ?>
    <div id="hmw_wrap" class="d-flex flex-row my-3 bg-light">
		<?php echo $view->getAdminTabs( HMW_Classes_Tools::getValue( 'tab', 'hmw_permalinks' ) ); ?>
        <div class="hmw_row d-flex flex-row bg-white px-3">
            <div class="hmw_col flex-grow-1 mr-3">
                <form method="POST">
					<?php wp_nonce_field( 'hmw_advsettings', 'hmw_nonce' ) ?>
                    <input type="hidden" name="action" value="hmw_advsettings"/>

                    <div class="card p-0 col-sm-12 tab-panel">
                        <h3 class="card-title bg-brown text-white p-2"><?php _e( 'Rollback Settings', _HMW_PLUGIN_NAME_ ); ?>:</h3>
                        <div class="card-body">

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-4 p-0 font-weight-bold">
									<?php _e( 'Custom Safe URL Param', _HMW_PLUGIN_NAME_ ); ?>:
                                    <div class="small text-black-50"><?php _e( "eg. disable_url, safe_url", _HMW_PLUGIN_NAME_ ); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control bg-input" name="hmw_disable_name" value="<?php echo HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ?>" placeholder="<?php echo HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ?>"/>
                                </div>
                                <div class="col-sm-12 pt-4">
                                    <div class="small text-black-50 text-center"><?php _e( "The Safe URL will set all the settings to default. Use it only if you're locked out", _HMW_PLUGIN_NAME_ ); ?></div>
                                    <div class="text-danger text-center"><?php echo '<strong>' . __( "Safe URL:", _HMW_PLUGIN_NAME_ ) . '</strong>' . ' ' . site_url() . "/wp-login.php?" . HMW_Classes_Tools::getOption( 'hmw_disable_name' ) . "=" . HMW_Classes_Tools::getOption( 'hmw_disable' ) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card p-0 col-sm-12 tab-panel">
                        <h3 class="card-title bg-brown text-white p-2"><?php _e( 'Loading Speed Settings', _HMW_PLUGIN_NAME_ ); ?>:</h3>
                        <div class="card-body">

							<?php if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'default' ) { ?>
                                <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3 text-black-50 text-center">
									<?php echo __( 'First, you need to switch Hide My Wp from Default mode to Safe Mode or Ghost Mode.', _HMW_PLUGIN_NAME_ ) ?>
                                </div>
							<?php } else { ?>
								<?php if ( ! HMW_Classes_Tools::isPluginActive( 'wp-rocket/wp-rocket.php' ) &&
								           ! HMW_Classes_Tools::isPluginActive( 'wp-super-cache/wp-cache.php' ) &&
								           ! HMW_Classes_Tools::isPluginActive( 'wp-fastest-cache/wpFastestCache.php' ) &&
								           ! HMW_Classes_Tools::isPluginActive( 'powered-cache/powered-cache.php' ) &&
								           ! HMW_Classes_Tools::isPluginActive( 'w3-total-cache/w3-total-cache.php' ) &&
								           ! HMW_Classes_Tools::isPluginActive( 'autoptimize/autoptimize.php' ) ) { ?>
                                    <div class="col-sm-12 row mb-1 ml-1">
                                        <div class="checker col-sm-12 row my-2 py-1">
                                            <div class="col-sm-12 p-0 switch switch-sm">
                                                <input type="hidden" name="hmw_file_cache" value="0"/>
                                                <input type="checkbox" id="hmw_file_cache" name="hmw_file_cache" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_file_cache' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                                <label for="hmw_file_cache"><?php _e( 'Optimize CSS and JS files', _HMW_PLUGIN_NAME_ ); ?></label>
                                                <div class="offset-1 text-black-50"><?php echo __( 'Cache CSS, JS and Images to increase the frontend loading speed.', _HMW_PLUGIN_NAME_ ); ?></div>
                                                <div class="offset-1 text-black-50"><?php echo sprintf( __( 'Check the website loading speed with %sPingdom Tool%s', _HMW_PLUGIN_NAME_ ), '<a href="https://tools.pingdom.com/" target="_blank">', '</a>' ); ?></div>
                                            </div>
                                        </div>
                                    </div>
								<?php } ?>
							<?php } ?>
                        </div>
                    </div>
                    <div class="card p-0 col-sm-12 tab-panel">
                        <h3 class="card-title bg-brown text-white p-2"><?php _e( 'Compatibility Settings', _HMW_PLUGIN_NAME_ ); ?>:</h3>
                        <div class="card-body">

							<?php if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) == 'default' ) { ?>
                                <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3 text-black-50 text-center">
									<?php echo __( 'First, you need to switch Hide My Wp from Default mode to Safe Mode or Ghost Mode.', _HMW_PLUGIN_NAME_ ) ?>
                                </div>
							<?php } else { ?>
                                <div class="col-sm-12 row mb-1 ml-1">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmw_firstload" value="0"/>
                                            <input type="checkbox" id="hmw_firstload" name="hmw_firstload" class="switch" <?php echo(HMW_Classes_Tools::getOption( 'hmw_firstload' ) ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmw_firstload"><?php _e( 'Load As Must Use Plugin', _HMW_PLUGIN_NAME_ ); ?></label>
                                            <a href="https://hidemywpghost.com/kb/advanced-wp-security/#firstload" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                            <div class="offset-1 text-black-50"><?php _e( 'Force Hide My WP Ghost to load as a Must Use plugin.', _HMW_PLUGIN_NAME_ ); ?></div>
                                            <div class="offset-1 text-black-50"><?php _e( '(compatibility with Manage WP plugin and Token based login plugins)', _HMW_PLUGIN_NAME_ ); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmw_laterload" value="0"/>
                                            <input type="checkbox" id="hmw_laterload" name="hmw_laterload" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_laterload' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmw_laterload"><?php _e( 'Late Loading', _HMW_PLUGIN_NAME_ ); ?></label>
                                            <a href="https://hidemywpghost.com/kb/advanced-wp-security/#late_loading" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                            <div class="offset-1 text-black-50"><?php echo __( 'Load HMW after all plugins are loaded. Useful for CDN plugins (eg. CDN Enabler).', _HMW_PLUGIN_NAME_ ); ?></div>
                                            <div class="offset-1 text-black-50"><?php echo __( '(only if other cache plugins request this)', _HMW_PLUGIN_NAME_ ); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmw_remove_third_hooks" value="0"/>
                                            <input type="checkbox" id="hmw_remove_third_hooks" name="hmw_remove_third_hooks" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_remove_third_hooks' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                            <label for="hmw_remove_third_hooks"><?php _e( 'Clean Login Page', _HMW_PLUGIN_NAME_ ); ?></label>
                                            <a href="https://hidemywpghost.com/kb/advanced-wp-security/#clean_login_page" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                            <div class="offset-1 text-black-50"><?php _e( 'Cancel the login hooks from other plugins and themes to prevent them from changing the Hide My WordPress redirects.', _HMW_PLUGIN_NAME_ ); ?><?php _e( '(not recommended)', _HMW_PLUGIN_NAME_ ); ?></div>
                                        </div>
                                    </div>
                                </div>
							<?php } ?>


                        </div>
                    </div>
                    <div class="card p-0 col-sm-12 tab-panel">
                        <h3 class="card-title bg-brown text-white p-2"><?php _e( 'Notification Settings', _HMW_PLUGIN_NAME_ ); ?>:</h3>
                        <div class="card-body">
                            <div class="col-sm-12 row mb-1 ml-1">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmw_security_alert" value="0"/>
                                        <input type="checkbox" id="hmw_security_alert" name="hmw_security_alert" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_security_alert' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmw_security_alert"><?php _e( 'Security Check Notification', _HMW_PLUGIN_NAME_ ); ?></label>
                                        <a href="https://hidemywpghost.com/kb/advanced-wp-security/#email_notification" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo __( "Show Security Check notification when it's not checked every week.", _HMW_PLUGIN_NAME_ ); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmw_send_email" value="0"/>
                                        <input type="checkbox" id="hmw_send_email" name="hmw_send_email" class="switch" <?php echo( HMW_Classes_Tools::getOption( 'hmw_send_email' ) ? 'checked="checked"' : '' ) ?> value="1"/>
                                        <label for="hmw_send_email"><?php _e( 'Email notification', _HMW_PLUGIN_NAME_ ); ?></label>
                                        <div class="offset-1 text-black-50"><?php _e( 'Send me an email with the changed admin and login URLs', _HMW_PLUGIN_NAME_ ); ?></div>
                                    </div>
                                </div>


                            </div>

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-4 p-1 font-weight-bold">
									<?php _e( 'Email Address', _HMW_PLUGIN_NAME_ ); ?>:
                                </div>
                                <div class="col-sm-8 p-0 input-group input-group">
									<?php
									$email = HMW_Classes_Tools::getOption( 'hmw_email_address' );
									if ( $email == '' ) {
										global $current_user;
										$email = $current_user->user_email;
									}
									?>
                                    <input type="text" class="form-control bg-input" name="hmw_email_address" value="<?php echo $email ?>" placeholder="Email address ..."/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0px 0px 8px -3px #444;">
                        <button type="submit" class="btn rounded-0 btn-success btn-lg px-5 mr-5 save"><?php _e( 'Save', _HMW_PLUGIN_NAME_ ); ?></button>
                        <a href="https://wordpress.org/support/plugin/hide-my-wp/reviews/?rate=5#new-post" target="_blank" style="color: #ff005e;"><?php echo sprintf( __( 'Love Hide My WP %s? Show us ;)', _HMW_PLUGIN_NAME_ ), _HMW_VER_NAME_ ); ?></a>
                    </div>
                </form>
            </div>
            <div class="hmw_col hmw_col_side">
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

                <div class="hmw_col hmw_col_side">
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


					<?php echo $view->getView( 'Support' ) ?>

                </div>
            </div>
        </div>
    </div>
<?php }