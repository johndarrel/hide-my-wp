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
                                        <label for="prevent_slow_loading"><?php echo esc_html__('Prevent Broken Website Layout', 'hide-my-wp'); ?>
                                            <a href="https://hidemywpghost.com/kb/advanced-wp-security/#prevent_slow_loading" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            <span class="text-black-50 small">(<?php echo esc_html__("recommended", 'hide-my-wp'); ?>)</span> </label>
                                        </label>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__("If the rewrite rules are not loading correctly in the config file, do not load the plugin and do not change the paths.", 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div id="compatibility" class="card col-sm-12 p-0 m-0 tab-panel">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Compatibility Settings', 'hide-my-wp'); ?></h3>
                        <div class="card-body">

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-3 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__('Server Type', 'hide-my-wp'); ?>:</div>
                                </div>
                                <div class="col-sm-9 p-0 input-group mb-1">
                                    <select name="hmwp_server_type" class="form-control bg-input">
                                        <option value="auto" <?php selected('auto', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?>><?php echo esc_html__("Autodetect", 'hide-my-wp') ?></option>
                                        <option value="apache" <?php selected('apache', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?>><?php echo esc_html__("Apache", 'hide-my-wp') ?></option>
                                        <option value="iis" <?php selected('iis', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?>><?php echo esc_html__("IIS Windows", 'hide-my-wp') ?></option>
                                        <option value="nginx" <?php selected('nginx', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?>><?php echo esc_html__("Nginx", 'hide-my-wp') ?></option>
                                        <option value="litespeed" <?php selected('litespeed', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?>><?php echo esc_html__("LiteSpeed", 'hide-my-wp') ?></option>
                                        <option value="siteground" <?php selected('siteground', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?>><?php echo esc_html__("SiteGround", 'hide-my-wp') ?></option>
                                        <option value="cloudpanel" <?php selected('cloudpanel', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?> ><?php echo esc_html__("Cloud Panel", 'hide-my-wp') ?></option>
                                        <option value="flywheel" <?php selected('flywheel', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?> ><?php echo esc_html__("Flywheel", 'hide-my-wp') ?></option>
                                        <option value="inmotion" <?php selected('inmotion', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?> ><?php echo esc_html__("Inmotion", 'hide-my-wp') ?></option>
                                        <option value="wpengine" <?php selected('wpengine', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?> ><?php echo esc_html__("WP Engine", 'hide-my-wp') ?></option>
                                        <option value="bitnami" <?php selected('bitnami', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?> ><?php echo esc_html__("AWS Bitnami", 'hide-my-wp') ?></option>
                                        <option value="godaddy" <?php selected('godaddy', HMWP_Classes_Tools::getOption('hmwp_server_type')) ?> ><?php echo esc_html__("Godaddy", 'hide-my-wp') ?></option>
                                    </select>

                                    <div class="p-1 text-left">
                                        <div class="text-black-50"><?php echo esc_html__('Choose the type of Nginx server you are using to get the most suitable configuration for your server.', 'hide-my-wp'); ?></div>
                                        <div class="text-danger"><?php echo esc_html__('Only change this option if the plugin fails to identify the server type correctly.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>

                            </div>

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-3 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__('Plugin Loading Hook', 'hide-my-wp'); ?>:</div>
                                </div>
                                <div class="col-sm-9 p-0 input-group mb-1">
                                    <select multiple name="hmwp_loading_hook[]" class="form-control bg-input selectpicker">
                                        <option value="first" <?php echo ((in_array('first', HMWP_Classes_Tools::getOption('hmwp_loading_hook')) || HMWP_Classes_Tools::getOption('hmwp_firstload')) ? 'selected="select"' : '' ) ?>><?php echo esc_html__("Must Use Plugin Loading", 'hide-my-wp') ?></option>
                                        <option value="priority" <?php echo ((in_array('priority', HMWP_Classes_Tools::getOption('hmwp_loading_hook')) || HMWP_Classes_Tools::getOption('hmwp_priorityload')) ? 'selected="select"' : '' ) ?>><?php echo esc_html__("Priority Loading", 'hide-my-wp') ?></option>
                                        <option value="normal" <?php echo ((in_array('normal', HMWP_Classes_Tools::getOption('hmwp_loading_hook'))  || (!HMWP_Classes_Tools::getOption('hmwp_laterload') && !HMWP_Classes_Tools::getOption('hmwp_priorityload'))) ? 'selected="select"' : '') ?>><?php echo esc_html__("Normal Loading", 'hide-my-wp') ?> (<?php echo esc_html__("recommended", 'hide-my-wp'); ?>)</option>
                                        <option value="late" <?php echo ((in_array('late', HMWP_Classes_Tools::getOption('hmwp_loading_hook')) || HMWP_Classes_Tools::getOption('hmwp_laterload')) ? 'selected="select"' : '') ?>><?php echo esc_html__("Late Loading", 'hide-my-wp') ?></option>
                                    </select>
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/advanced-wp-security/#hook_loading') ?>" target="_blank" class="d-inline-block m-2" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help d-inline"></i></a>

                                    <div class="col-sm-12 p-0 m-0 ">
                                        <div class="text-black-50 small mt-2"><strong><?php echo esc_html__("Must Use Plugin Loading", 'hide-my-wp') ?></strong> - <?php echo esc_html__('Load the plugin as a Must Use plugin.', 'hide-my-wp'); ?><br><?php echo esc_html__('Compatibility with Manage WP plugin', 'hide-my-wp'); ?>. <?php echo esc_html__('Compatibility with Token Based Login plugins', 'hide-my-wp'); ?>. </div>
                                        <div class="text-black-50 small mt-2"><strong><?php echo esc_html__("Priority Loading", 'hide-my-wp') ?></strong> - <?php echo esc_html__('Load before all plugins are loaded. On "plugins_loaded" hook.', 'hide-my-wp'); ?></div>
                                        <div class="text-black-50 small mt-2"><strong><?php echo esc_html__("Normal Loading", 'hide-my-wp') ?></strong> - <?php echo esc_html__('Load when the plugins are initialized. On "init" hook.', 'hide-my-wp'); ?></div>
                                        <div class="text-black-50 small mt-2"><strong><?php echo esc_html__("Late Loading", 'hide-my-wp') ?></strong> - <?php echo esc_html__('Load after all plugins are loaded. On "template_redirects" hook.', 'hide-my-wp'); ?></div>
                                        <div class="text-black-50 mt-2 small"><?php echo esc_html__('(multiple options are available)', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>

                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_remove_third_hooks" value="0"/>
                                        <input type="checkbox" id="hmwp_remove_third_hooks" name="hmwp_remove_third_hooks" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_remove_third_hooks') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_remove_third_hooks"><?php echo esc_html__('Clean Login Page', 'hide-my-wp'); ?></label>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/advanced-wp-security/#clean_login_page') ?>" target="_blank" class="d-inline-block ml-2"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('Cancel the login hooks from other plugins and themes to prevent unwanted login redirects.', 'hide-my-wp'); ?></div>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('(useful when the theme is adding wrong admin redirects or infinite redirects)', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if(HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed()){ ?>
                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_rewrites_in_wp_rules" value="0"/>
                                            <input type="checkbox" id="hmwp_rewrites_in_wp_rules" name="hmwp_rewrites_in_wp_rules" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_rewrites_in_wp_rules') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_rewrites_in_wp_rules"><?php echo esc_html__('Add Rewrites in WordPress Rules Section', 'hide-my-wp'); ?> <span class="text-black-50 small">(<?php echo esc_html__("recommended", 'hide-my-wp'); ?>)</span> </label></label>
                                            <div class="offset-1 text-black-50"><?php echo esc_html__("This option will add rewrite rules to the .htaccess file in the WordPress rewrite rules area between the comments # BEGIN WordPress and # END WordPress.", 'hide-my-wp'); ?></div>
                                            <div class="offset-1 text-black-50 mt-2"><?php echo esc_html__("Some plugins may remove custom rewrite rules from the .htaccess file, especially if it's writable, which can affect the functionality of custom paths..", 'hide-my-wp'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>


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
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/advanced-wp-security/#email_notification') ?>" target="_blank" class="d-inline-block ml-2"><i class="dashicons dashicons-editor-help d-inline"></i></a>
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
                        <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save" ><?php echo esc_html__('Save', 'hide-my-wp'); ?></button>
                    </div>
                </form>

            </div>
        <div class="hmwp_col hmwp_col_side p-0 m-0 mr-2">
            <?php $view->show('blocks/ChangeCacheFiles'); ?>
            <?php $view->show('blocks/SecurityCheck'); ?>
        </div>
</div>
