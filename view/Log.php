<div id="hmw_wrap" class="d-flex flex-row my-3 bg-light">
    <?php echo $view->getAdminTabs(HMW_Classes_Tools::getValue('tab', 'hmw_permalinks')); ?>
    <div class="hmw_row d-flex flex-row bg-white px-3">
        <div class="hmw_col flex-grow-1 mr-3">
            <form method="POST">
                <?php wp_nonce_field('hmw_logsettings', 'hmw_nonce') ?>
                <input type="hidden" name="action" value="hmw_logsettings"/>

                <div class="card p-0 col-sm-12 tab-panel">
                        <h3 class="card-title bg-brown text-white p-2"><?php _e('Events Settings', _HMW_PLUGIN_NAME_); ?>:</h3>
                        <div class="card-body">
                            <div class="box" data-toggle="popover" data-html="true" data-placement="top" data-content="<?php echo sprintf(__('This feature requires %sHide My WP Ghost%s.', _HMW_PLUGIN_NAME_), "<a href='http://hidemywpghost.com/' target='_blank'>", "</a>") ?>">
                                <div class="ribbon"><span><?php echo __('PRO', _HMW_PLUGIN_NAME_) ?></span></div>
                            </div>

                            <div style="opacity: 0.3">
                                <div class="col-sm-12 row mb-1 py-3 mx-2 ">
                                    <div class="checker col-md-12 row my-2 py-1">
                                        <div class="col-md-12 p-0 switch switch-sm">
                                            <div class="hmw_pro">
                                                <img src="<?php echo _HMW_THEME_URL_ . 'img/pro.png' ?>">
                                            </div>
                                            <label for="hmw_activity_log"><?php _e('Log Users Events', _HMW_PLUGIN_NAME_); ?></label>
                                            <div class="offset-1 text-black-50"><?php _e('Track and Log events that happens on your WordPress site!', _HMW_PLUGIN_NAME_); ?></div>
                                        </div>
                                    </div>
                                </div>


                                <?php if (HMW_Classes_Tools::getOption('hmw_bruteforce')) { ?>
                                    <div class="col-sm-12 row mb-1 py-3 mx-2 ">
                                        <div class="checker col-md-12 row my-2 py-1">
                                            <div class="col-md-12 p-0 switch switch-sm">
                                                <div class="hmw_pro">
                                                    <img src="<?php echo _HMW_THEME_URL_ . 'img/pro.png' ?>">
                                                </div>
                                                <label for="hmw_bruteforce_log"><?php _e('Log Brute Force Attempts', _HMW_PLUGIN_NAME_); ?></label>
                                                <div class="offset-1 text-black-50"><?php _e('Track and Log brute force attempts', _HMW_PLUGIN_NAME_); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>

                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php _e('Log Use Roles', _HMW_PLUGIN_NAME_); ?>:</div>
                                        <div class="text-black-50"><?php _e('Hold Control key to select multiple user roles', _HMW_PLUGIN_NAME_); ?></div>
                                        <div class="text-black-50"><?php _e("Don't select any role if you want to log all user roles", _HMW_PLUGIN_NAME_); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group" style="opacity: 0.3;">
                                        <select multiple name="hmw_activity_log_roles[]" class="form-control bg-input mb-1">
                                            <?php
                                            global $wp_roles;
                                            $roles = $wp_roles->get_names();
                                            foreach ($roles as $key => $role) {
                                                echo '<option value="' . $key . '" ' . (in_array($key, (array)HMW_Classes_Tools::getOption('hmw_activity_log_roles')) ? 'selected="selected"' : '') . '>' . $role . '</option>';
                                            } ?>
                                        </select>
                                    </div>

                                </div>
                            </div>
                        </div>
                </div>
            </form>
        </div>
        <div class="hmw_col hmw_col_side">
            <div class="card col-md-12 p-0">
                <div class="card-body f-gray-dark text-left border-bottom">
                    <h3 class="card-title"><?php _e('Log Events', _HMW_PLUGIN_NAME_); ?></h3>
                    <div class="text-info mb-3"><?php echo __("Monitor everything that happens on your WordPress site!", _HMW_PLUGIN_NAME_); ?></div>
                    <div class="text-info mb-3"><?php echo __("It's safe to know what happened on your website at any time, in admin and on frontend.", _HMW_PLUGIN_NAME_); ?></div>
                    <div class="text-info mb-3"><?php echo __("All the logs are saved on our Cloud Servers and your data is safe in case you reinstall the plugin", _HMW_PLUGIN_NAME_); ?></div>
                </div>
            </div>
            <div class="card col-md-12 p-0">
                <div class="card-body f-gray-dark text-left border-bottom">
                    <h3 class="card-title"><?php _e('Features', _HMW_PLUGIN_NAME_); ?></h3>
                    <ul class="text-info" style="margin-left: 16px; list-style: circle;">
                        <li class="mb-2"><?php echo __("Monitor, track and log events on your website", _HMW_PLUGIN_NAME_); ?></li>
                        <li class="mb-2"><?php echo __("Know what the other users are doing on your website and when", _HMW_PLUGIN_NAME_); ?></li>
                        <li class="mb-2"><?php echo __("You can set to receive email with alerts for one or more actions", _HMW_PLUGIN_NAME_); ?></li>
                        <li class="mb-2"><?php echo __("Filter events and users", _HMW_PLUGIN_NAME_); ?></li>
                        <li><?php echo __("Compatible with all themes and plugins", _HMW_PLUGIN_NAME_); ?></li>
                    </ul>
                </div>
            </div>

            <?php echo $view->getView('Support') ?>

        </div>
    </div>
</div>
