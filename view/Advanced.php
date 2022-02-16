<?php if(!isset($view)) return; ?>
<noscript> <style>#hmwp_wrap .tab-panel:not(.tab-panel-first){display: block}</style> </noscript>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
<?php echo $view->getAdminTabs(HMWP_Classes_Tools::getValue('page', 'hmwp_advanced')); ?>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 px-3 py-3 mr-2 mb-3 bg-white">
                <form method="POST">
                    <?php wp_nonce_field('hmwp_advsettings', 'hmwp_nonce') ?>
                    <input type="hidden" name="action" value="hmwp_advsettings"/>

                    <div id="rollback" class="card col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Custom Safe URL', 'hide-my-wp'); ?></h3>
                        <div class="card-body">

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-4 p-0 font-weight-bold">
                                    <?php echo esc_html__('Custom Safe URL Param', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__("eg. disable_url, safe_url", 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control bg-input" name="hmwp_disable_name" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_disable_name') ?>" placeholder="<?php echo HMWP_Classes_Tools::getOption('hmwp_disable_name') ?>"/>
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/advanced-wp-security/#custom_safe_url') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                </div>
                                <div class="col-sm-12 py-3">
                                    <div class="small text-black-50 text-center my-2"><?php echo esc_html__("The Safe URL will deactivate all the custom paths. Use it only if you can't login.", 'hide-my-wp'); ?></div>
                                    <div class="alert-danger p-3 text-center"><?php echo '<strong>' . esc_html__("Safe URL:", 'hide-my-wp') . '</strong>' . ' <a href="'.site_url() . "/wp-login.php?" . HMWP_Classes_Tools::getOption('hmwp_disable_name') . "=" . HMWP_Classes_Tools::getOption('hmwp_disable').'" target="_blank">' . site_url() . "/wp-login.php?" . HMWP_Classes_Tools::getOption('hmwp_disable_name') . "=" . HMWP_Classes_Tools::getOption('hmwp_disable') . '</a>' ?></div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="prevent_slow_loading" value="0"/>
                                        <input type="checkbox" id="prevent_slow_loading" name="prevent_slow_loading" class="switch" <?php echo(HMWP_Classes_Tools::getOption('prevent_slow_loading') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="prevent_slow_loading"><?php echo esc_html__('Prevent Slow Loading Website', 'hide-my-wp'); ?></label>
                                        <a href="https://hidemywpghost.com/kb/advanced-wp-security/#prevent_slow_loading" target="_blank" class="d-inline-block ml-2"><i class="fa fa-question-circle"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__("Don't load the plugin if the rewrite rules are not loading correctly in the config file.", 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div id="compatibility" class="card col-sm-12 p-0 m-0 tab-panel">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Compatibility Settings', 'hide-my-wp'); ?></h3>
                        <div class="card-body">

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_firstload" value="0"/>
                                        <input type="checkbox" id="hmwp_firstload" name="hmwp_firstload" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_firstload') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_firstload"><?php echo esc_html__('Compatibility with Manage WP plugin', 'hide-my-wp'); ?></label>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/advanced-wp-security/#firstload') ?>" target="_blank" class="d-inline-block ml-2"><i class="dashicons dashicons-editor-help"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('Load the plugin as a Must Use plugin.', 'hide-my-wp'); ?></div>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('(compatibility with Token based login plugins)', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_laterload" value="0"/>
                                        <input type="checkbox" id="hmwp_laterload" name="hmwp_laterload" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_laterload') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_laterload"><?php echo esc_html__('Late Loading', 'hide-my-wp'); ?></label>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/advanced-wp-security/#late_loading') ?>" target="_blank" class="d-inline-block ml-2"><i class="dashicons dashicons-editor-help"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('Load HMWP after all plugins are loaded.', 'hide-my-wp'); ?></div>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('(compatibility with CDN Enabler and other cache plugins)', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_remove_third_hooks" value="0"/>
                                        <input type="checkbox" id="hmwp_remove_third_hooks" name="hmwp_remove_third_hooks" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_remove_third_hooks') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_remove_third_hooks"><?php echo esc_html__('Clean Login Page', 'hide-my-wp'); ?></label>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/advanced-wp-security/#clean_login_page') ?>" target="_blank" class="d-inline-block ml-2"><i class="dashicons dashicons-editor-help"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('Cancel the login hooks from other plugins and themes to prevent unwanted login redirects.', 'hide-my-wp'); ?></div>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('(useful when the theme is adding wrong admin redirects or infinite redirects)', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>

                    <div id="notification" class="card col-sm-12 p-0 m-0 tab-panel">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Notification Settings', 'hide-my-wp'); ?></h3>
                        <div class="card-body">

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_send_email" value="0"/>
                                        <input type="checkbox" id="hmwp_send_email" name="hmwp_send_email" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_send_email') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_send_email"><?php echo esc_html__('Email Notification', 'hide-my-wp'); ?></label>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/advanced-wp-security/#email_notification') ?>" target="_blank" class="d-inline-block ml-2"><i class="dashicons dashicons-editor-help"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('Send me an email with the changed admin and login URLs', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3 hmwp_send_email">
                                <div class="col-sm-4 p-1 font-weight-bold">
                                    <?php echo esc_html__('Email Address', 'hide-my-wp'); ?>:
                                </div>
                                <div class="col-sm-8 p-0 input-group input-group">
                                    <?php
                                    $email = HMWP_Classes_Tools::getOption('hmwp_email_address');
                                    if ($email == '' ) {
                                        global $current_user;
                                        $email = $current_user->user_email;
                                    }
                                    ?>
                                    <input type="text" class="form-control bg-input" name="hmwp_email_address" value="<?php echo esc_attr($email) ?>" placeholder="Email address ..."/>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0 0 8px -3px #444;">
                        <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__('Save', 'hide-my-wp'); ?></button>
                    </div>
                </form>

            </div>
        <div class="hmwp_col hmwp_col_side p-0 m-0 mr-2">
            <?php $view->show('blocks/ChangeCacheFiles'); ?>
            <?php $view->show('blocks/SecurityCheck'); ?>
        </div>
</div>
