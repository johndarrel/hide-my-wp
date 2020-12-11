<div id="hmw_wrap" class="d-flex flex-row my-3 bg-light">
    <?php echo $view->getAdminTabs(HMW_Classes_Tools::getValue('tab', 'hmw_permalinks')); ?>
    <div class="hmw_row d-flex flex-row bg-white px-3">
        <div class="hmw_col flex-grow-1 mr-3">
            <form method="POST">
                <?php wp_nonce_field('hmw_brutesettings', 'hmw_nonce') ?>
                <input type="hidden" name="action" value="hmw_brutesettings"/>

                <div class="card p-0 col-sm-12 tab-panel">
                    <div class="card-body">
                        <div class="col-sm-12 row mb-1 py-3 mx-2 ">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="checkbox" id="hmw_bruteforce" name="hmw_bruteforce" class="switch" <?php echo(HMW_Classes_Tools::getOption('hmw_bruteforce') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmw_bruteforce"><?php _e('Use Brute Force Protection', _HMW_PLUGIN_NAME_); ?></label>
                                    <a href="https://hidemywpghost.com/kb/brute-force-attack-protection/#activate_brute_force" target="_blank" class="d-inline-block ml-2" ><i class="fa fa-question-circle"></i></a>
                                    <div class="offset-1 text-black-50"><?php _e('Protects your website against brute force login attacks', _HMW_PLUGIN_NAME_); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="hmw_brute_enabled" <?php echo(!HMW_Classes_Tools::getOption('hmw_bruteforce') ? 'style="display:none"' : '') ?> >

                            <div class="border-top"></div>
                            <input type="hidden" value="1" name="brute_use_math">

                            <div class="group_autoload col-sm-12 d-flex justify-content-center btn-group mt-3" role="group" data-toggle="button">
                                <button type="button" class="btn btn-lg btn-outline-info brute_use_math m-1 py-3 px-4 active"><?php _e('Math Check protection', _HMW_PLUGIN_NAME_); ?></button>

                                <div class="hmw_pro mt-1" style="position: relative" data-toggle="popover" data-html="true" data-placement="top" data-content="<?php echo sprintf(__('This feature requires %sHide My WP Ghost%s.', _HMW_PLUGIN_NAME_), "<a href='https://hidemywpghost.com/hide-my-wp-pricing/' target='_blank'>", "</a>") ?>">
                                    <div class="box" data-toggle="popover" data-html="true" data-placement="top" data-content="<?php echo sprintf(__('This feature requires %sHide My WP Ghost%s.', _HMW_PLUGIN_NAME_), "<a href='https://hidemywpghost.com/hide-my-wp-pricing/' target='_blank'>", "</a>") ?>">
                                        <div class="ribbon"><span><?php echo __('PRO', _HMW_PLUGIN_NAME_) ?></span>
                                        </div>
                                    </div>
                                    <img src="<?php echo _HMW_THEME_URL_ . 'img/pro_captcha.png' ?>">
                                </div>
                            </div>

                            <script>
                                (function ($) {
                                    $(document).ready(function () {
                                        $("button.brute_use_math").on('click', function () {
                                            $('input[name=brute_use_math]').val(1);
                                            $('.group_autoload button').removeClass('active');
                                            $('.tab-panel.brute_use_math').show();
                                        });
                                    });
                                })(jQuery);
                            </script>
                            <div class="tab-panel brute_use_math">
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php _e('Max fail attempts', _HMW_PLUGIN_NAME_); ?>:
                                        <div class="small text-black-50"><?php _e('Block IP on login page', _HMW_PLUGIN_NAME_); ?></div>
                                    </div>
                                    <div class="col-md-2 p-0 input-group">
                                        <input type="text" class="form-control bg-input" name="brute_max_attempts" value="<?php echo HMW_Classes_Tools::getOption('brute_max_attempts') ?>"/>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php _e('Ban duration', _HMW_PLUGIN_NAME_); ?>:
                                        <div class="small text-black-50"><?php _e('No. of seconds', _HMW_PLUGIN_NAME_); ?></div>
                                    </div>
                                    <div class="col-md-2 p-0 input-group input-group">
                                        <input type="text" class="form-control bg-input" name="brute_max_timeout" value="<?php echo HMW_Classes_Tools::getOption('brute_max_timeout') ?>"/>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php _e('Lockout Message', _HMW_PLUGIN_NAME_); ?>:
                                        <div class="small text-black-50"><?php _e('Show message instead of login form', _HMW_PLUGIN_NAME_); ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group input-group">
                                        <textarea type="text" class="form-control bg-input" name="hmw_brute_message" style="height: 80px"><?php echo HMW_Classes_Tools::getOption('hmw_brute_message') ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="border-top">
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="box" data-toggle="popover" data-html="true" data-placement="top" data-content="<?php echo sprintf(__('This feature requires %sHide My WP Ghost%s.', _HMW_PLUGIN_NAME_), "<a href='https://hidemywpghost.com/hide-my-wp-pricing/' target='_blank'>", "</a>") ?>">
                                        <div class="ribbon"><span><?php echo __('PRO', _HMW_PLUGIN_NAME_) ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 p-0 font-weight-bold" style="opacity: 0.3;">
                                        <?php _e('Whitelist IPs', _HMW_PLUGIN_NAME_); ?>:
                                        <div class="small text-black-50"><?php echo sprintf(__('You can white-list a single IP like 192.168.0.1 or a range of 245 IPs like 192.168.0.*. Find your IP with %s', _HMW_PLUGIN_NAME_), '<a href="https://whatismyipaddress.com/" target="_blank">https://whatismyipaddress.com/</a>') ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group input-group" style="opacity: 0.3;">
                                        <textarea type="text" class="form-control bg-input" name="whitelist_ip" style="height: 100px"></textarea>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="box" data-toggle="popover" data-html="true" data-placement="top" data-content="<?php echo sprintf(__('This feature requires %sHide My WP Ghost%s.', _HMW_PLUGIN_NAME_), "<a href='https://hidemywpghost.com/hide-my-wp-pricing/' target='_blank'>", "</a>") ?>">
                                        <div class="ribbon"><span><?php echo __('PRO', _HMW_PLUGIN_NAME_) ?></span>
                                        </div>
                                    </div>

                                    <div class="col-md-4 p-0 font-weight-bold" style="opacity: 0.3;">
                                        <?php _e('Ban IPs', _HMW_PLUGIN_NAME_); ?>:
                                        <div class="small text-black-50"><?php echo __('You can ban a single IP like 192.168.0.1 or a range of 245 IPs like 192.168.0.*. These IPs will not be able to access the login page.', _HMW_PLUGIN_NAME_) ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group input-group" style="opacity: 0.3;">
                                        <textarea type="text" class="form-control bg-input" name="banlist_ip" style="height: 100px"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0px 0px 8px -3px #444;">
                    <button type="submit" class="btn rounded-0 btn-success btn-lg px-5 mr-5 save"><?php _e('Save', _HMW_PLUGIN_NAME_); ?></button>
                    <a href="https://wordpress.org/support/plugin/hide-my-wp/reviews/?rate=5#new-post" target="_blank" style="color: #ff005e;"><?php echo sprintf( __( 'Love Hide My WP %s? Show us ;)', _HMW_PLUGIN_NAME_ ), _HMW_VER_NAME_ ); ?></a>
                </div>
            </form>

            <div class="card p-0 col-sm-12 tab-panel">
                <div class="card-body">
                    <h3 class="card-title"><?php _e('Blocked IPs', _HMW_PLUGIN_NAME_); ?>:</h3>
                    <div class="mt-3 mb-1" style="display: block;">
                        <div class="offset-10 col-md-2 py-1">
                            <form method="POST">
                                <?php wp_nonce_field('hmw_deleteallips', 'hmw_nonce') ?>
                                <input type="hidden" name="action" value="hmw_deleteallips"/>
                                <button type="submit" class="btn rounded-0 btn-default save py-0"><?php _e('Unlock all', _HMW_PLUGIN_NAME_); ?></button>
                            </form>
                        </div>
                        <div id="hmw_blockedips" class="col-sm-12 p-0"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hmw_col hmw_col_side">
            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-left border-bottom">
                    <h3 class="card-title"><?php _e('Brute Force Login Protection', _HMW_PLUGIN_NAME_); ?></h3>
                    <div class="text-info"><?php echo __("Protects your website against brute force login attacks using Hide My WordPress <br /><br /> A common threat web developers face is a password-guessing attack known as a brute force attack. A brute-force attack is an attempt to discover a password by systematically trying every possible combination of letters, numbers, and symbols until you discover the one correct combination that works. ", _HMW_PLUGIN_NAME_); ?>
                    </div>
                </div>
            </div>
            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-left border-bottom">
                    <h3 class="card-title"><?php _e('Features', _HMW_PLUGIN_NAME_); ?></h3>
                    <ul class="text-info" style="margin-left: 16px; list-style: circle;">
                        <li><?php echo __("Limit the number of allowed login attempts using normal login form", _HMW_PLUGIN_NAME_); ?></li>
                        <li><?php echo __("Math problem verification while logging in", _HMW_PLUGIN_NAME_); ?></li>
                        <li><?php echo __("Manually block/unblock IP addresses", _HMW_PLUGIN_NAME_); ?></li>
                        <li><?php echo __("Manually whitelist trusted IP addresses", _HMW_PLUGIN_NAME_); ?></li>
                        <li><?php echo __("Option to inform user about remaining attempts on login page", _HMW_PLUGIN_NAME_); ?></li>
                        <li><?php echo __("Custom message to show to blocked users", _HMW_PLUGIN_NAME_); ?></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
