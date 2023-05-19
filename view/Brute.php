<?php if(!isset($view)) return; ?>
<noscript> <style>#hmwp_wrap .tab-panel:not(.tab-panel-first){display: block}</style> </noscript>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
<?php echo $view->getAdminTabs(HMWP_Classes_Tools::getValue('page', 'hmwp_brute')); ?>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 px-3 py-3 mr-2 mb-3 bg-white">

            <div id="blocked" class="card col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Blocked IPs', 'hide-my-wp'); ?>
                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/#block_ip_report') ?>" target="_blank" class="d-inline-block ml-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                </h3>
                <div class="card-body">
                    <?php if (HMWP_Classes_Tools::getOption('hmwp_bruteforce') ) { ?>
                        <div class="mt-3 mb-1" style="display: block;">
                            <div class="py-1">
                                <div class="float-right my-1" onclick="jQuery('#hmwp_blockedips_form').submit()"><i class="dashicons dashicons-update" style="cursor: pointer"></i></div>
                                <div class="my-1">
                                    <form method="POST">
                                        <?php wp_nonce_field('hmwp_deleteallips', 'hmwp_nonce') ?>
                                        <input type="hidden" name="action" value="hmwp_deleteallips"/>
                                        <button type="submit" class="btn rounded-0 btn-default save py-1"><?php echo esc_html__('Unlock all', 'hide-my-wp'); ?></button>
                                    </form>
                                </div>

                            </div>
                            <form id="hmwp_blockedips_form" method="POST">
                                <?php wp_nonce_field('hmwp_blockedips', 'hmwp_nonce') ?>
                                <input type="hidden" name="action" value="hmwp_blockedips"/>
                            </form>
                            <div id="hmwp_blockedips" class="col-sm-12 p-0"></div>
                        </div>
                    <?php }else{ ?>
                        <div class="col-sm-12 p-1 text-center">
                            <div class="text-black-50 mb-2"><?php echo esc_html__('Activate the "Brute Force" option to see the user IP blocked report', 'hide-my-wp'); ?></div>
                            <a href="#brute" class="btn btn-default hmwp_nav_item" data-tab="brute"><?php echo esc_html__('Activate Brute Force Protection', 'hide-my-wp'); ?></a>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <form method="POST">
                <?php wp_nonce_field('hmwp_brutesettings', 'hmwp_nonce') ?>
                <input type="hidden" name="action" value="hmwp_brutesettings"/>

                <div id="brute" class="card col-sm-12 p-0 m-0 tab-panel ">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Brute Force', 'hide-my-wp'); ?></h3>
                    <div class="card-body">
                        <div class="col-sm-12 row mb-1 py-1 mx-2 ">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="checkbox" id="hmwp_bruteforce" name="hmwp_bruteforce" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_bruteforce') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_bruteforce"><?php echo esc_html__('Use Brute Force Protection', 'hide-my-wp'); ?></label>
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/#activate_brute_force') ?>" target="_blank" class="d-inline-block ml-2" ><i class="dashicons dashicons-editor-help"></i></a>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__('Protects your website against Brute Force login attacks.', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 py-1 mx-2 hmwp_bruteforce">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="checkbox" id="hmwp_bruteforce_lostpassword" name="hmwp_bruteforce_lostpassword" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_bruteforce_lostpassword') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_bruteforce_lostpassword"><?php echo esc_html__('Lost Password Form Protection', 'hide-my-wp'); ?></label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__('Activate the Brute Force protection on lost password form.', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

	                    <?php if ( get_option( 'users_can_register' ) || (HMWP_Classes_Tools::isPluginActive('woocommerce/woocommerce.php') && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) ) {?>
                            <div class="col-sm-12 row mb-1 py-1 mx-2 hmwp_bruteforce">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="checkbox" id="hmwp_bruteforce_register" name="hmwp_bruteforce_register" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_bruteforce_register') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_bruteforce_register"><?php echo esc_html__('Sign Up Form Protection', 'hide-my-wp'); ?></label>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('Activate the Brute Force protection on sign up form.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>
	                    <?php }?>

	                    <?php if (HMWP_Classes_Tools::isPluginActive('woocommerce/woocommerce.php') ) { ?>
                            <div class="col-sm-12 row mb-1 py-1 mx-2 hmwp_bruteforce">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="checkbox" id="hmwp_bruteforce_woocommerce" name="hmwp_bruteforce_woocommerce" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_bruteforce_woocommerce') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_bruteforce_woocommerce"><?php echo esc_html__('WooCommerce Support', 'hide-my-wp'); ?></label>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__('Activate the Brute Force protection for Woocommerce login/signup forms.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>
	                    <?php } ?>

                        <div class="hmwp_bruteforce">

                            <div class="border-top"></div>
                            <input type="hidden" value="<?php echo(HMWP_Classes_Tools::getOption('brute_use_math') ? '1' : '0') ?>" name="brute_use_math">
                            <input type="hidden" value="<?php echo(HMWP_Classes_Tools::getOption('brute_use_captcha') ? '1' : '0') ?>" name="brute_use_captcha">
                            <input type="hidden" value="<?php echo(HMWP_Classes_Tools::getOption('brute_use_captcha_v3') ? '1' : '0') ?>" name="brute_use_captcha_v3">

                            <div class="col-sm-12 group_autoload d-flex justify-content-center btn-group btn-group-lg mt-3 px-0" role="group" >
                                <button type="button" class="btn btn-outline-info brute_use_math mx-1 py-4 px-4 <?php echo(HMWP_Classes_Tools::getOption('brute_use_math') ? 'active' : '') ?>"><?php echo esc_html__('Math reCAPTCHA', 'hide-my-wp'); ?></button>
                                <button type="button" class="btn btn-outline-info brute_use_captcha mx-1 py-4 px-4 <?php echo(HMWP_Classes_Tools::getOption('brute_use_captcha') ? 'active' : '') ?>"><?php echo esc_html__("Google reCAPTCHA V2", 'hide-my-wp') ?></button>
                                <button type="button" class="btn btn-outline-info brute_use_captcha_v3 mx-1 py-4 px-4 <?php echo(HMWP_Classes_Tools::getOption('brute_use_captcha_v3') ? 'active' : '') ?>"><?php echo esc_html__("Google reCAPTCHA V3", 'hide-my-wp') ?></button>
                            </div>

                            <div class="brute_use_captcha" <?php echo(!HMWP_Classes_Tools::getOption('brute_use_captcha') ? 'style="display:none"' : '') ?>>
                                <div class="col-sm-12 text-center border-bottom border-light py-3 mx-0 my-3">
                                    <?php echo sprintf(esc_html__("%sClick here%s to create or view keys for Google reCAPTCHA v2.", 'hide-my-wp'), '<a href="https://www.google.com/recaptcha/admin/create" class="mx-1 text-link font-weight-bold text-uppercase" target="_blank">', '</a>'); ?>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Site key', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo sprintf(esc_html__("Site keys for %sGoogle reCaptcha%s.", 'hide-my-wp'), '<a href="https://www.google.com/recaptcha/admin#list" class="text-link" target="_blank">', '</a>'); ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group">
                                        <input type="text" class="form-control bg-input" name="brute_captcha_site_key" value="<?php echo HMWP_Classes_Tools::getOption('brute_captcha_site_key') ?>"/>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Secret Key', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo sprintf(esc_html__("Secret keys for %sGoogle reCAPTCHA%s.", 'hide-my-wp'), '<a href="https://www.google.com/recaptcha/admin#list" class="text-link" target="_blank">', '</a>'); ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group">
                                        <input type="text" class="form-control bg-input" name="brute_captcha_secret_key" value="<?php echo HMWP_Classes_Tools::getOption('brute_captcha_secret_key') ?>"/>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__('reCaptcha Theme', 'hide-my-wp'); ?>:</div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select name="brute_captcha_theme" class="form-control bg-input mb-1">
                                            <?php
                                            $themes = array(esc_html__('light', 'hide-my-wp'), esc_html__('dark', 'hide-my-wp'));
                                            foreach ($themes as $theme) {
                                                echo '<option value="' . $theme . '" ' . selected($theme, HMWP_Classes_Tools::getOption('brute_captcha_theme')) . '>' . ucfirst($theme) . '</option>';
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__('reCaptcha Language', 'hide-my-wp'); ?>:</div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select name="brute_captcha_language" class="form-control bg-input mb-1">
                                            <?php
                                            $languages = array(
                                                esc_html__('Auto Detect', 'hide-my-wp') => '',
                                                esc_html__('English', 'hide-my-wp') => 'en',
                                                esc_html__('Arabic', 'hide-my-wp') => 'ar',
                                                esc_html__('Bulgarian', 'hide-my-wp') => 'bg',
                                                esc_html__('Catalan Valencian', 'hide-my-wp') => 'ca',
                                                esc_html__('Czech', 'hide-my-wp') => 'cs',
                                                esc_html__('Danish', 'hide-my-wp') => 'da',
                                                esc_html__('German', 'hide-my-wp') => 'de',
                                                esc_html__('Greek', 'hide-my-wp') => 'el',
                                                esc_html__('British English', 'hide-my-wp') => 'en_gb',
                                                esc_html__('Spanish', 'hide-my-wp') => 'es',
                                                esc_html__('Persian', 'hide-my-wp') => 'fa',
                                                esc_html__('French', 'hide-my-wp') => 'fr',
                                                esc_html__('Canadian French', 'hide-my-wp') => 'fr_ca',
                                                esc_html__('Hindi', 'hide-my-wp') => 'hi',
                                                esc_html__('Croatian', 'hide-my-wp') => 'hr',
                                                esc_html__('Hungarian', 'hide-my-wp') => 'hu',
                                                esc_html__('Indonesian', 'hide-my-wp') => 'id',
                                                esc_html__('Italian', 'hide-my-wp') => 'it',
                                                esc_html__('Hebrew', 'hide-my-wp') => 'iw',
                                                esc_html__('Jananese', 'hide-my-wp') => 'ja',
                                                esc_html__('Korean', 'hide-my-wp') => 'ko',
                                                esc_html__('Lithuanian', 'hide-my-wp') => 'lt',
                                                esc_html__('Latvian', 'hide-my-wp') => 'lv',
                                                esc_html__('Dutch', 'hide-my-wp') => 'nl',
                                                esc_html__('Norwegian', 'hide-my-wp') => 'no',
                                                esc_html__('Polish', 'hide-my-wp') => 'pl',
                                                esc_html__('Portuguese', 'hide-my-wp') => 'pt',
                                                esc_html__('Romanian', 'hide-my-wp') => 'ro',
                                                esc_html__('Russian', 'hide-my-wp') => 'ru',
                                                esc_html__('Slovak', 'hide-my-wp') => 'sk',
                                                esc_html__('Slovene', 'hide-my-wp') => 'sl',
                                                esc_html__('Serbian', 'hide-my-wp') => 'sr',
                                                esc_html__('Swedish', 'hide-my-wp') => 'sv',
                                                esc_html__('Thai', 'hide-my-wp') => 'th',
                                                esc_html__('Turkish', 'hide-my-wp') => 'tr',
                                                esc_html__('Ukrainian', 'hide-my-wp') => 'uk',
                                                esc_html__('Vietnamese', 'hide-my-wp') => 'vi',
                                                esc_html__('Simplified Chinese', 'hide-my-wp') => 'zh_cn',
                                                esc_html__('Traditional Chinese', 'hide-my-wp') => 'zh_tw'
                                            );
                                            foreach ($languages as $key => $language) {
                                                echo '<option value="' . $language . '"  ' . selected($language, HMWP_Classes_Tools::getOption('brute_captcha_language')) . '>' . ucfirst($key) . '</option>';
                                            } ?>
                                        </select>
                                    </div>
                                </div>

                                <?php if (HMWP_Classes_Tools::getOption('brute_captcha_site_key') <> '' && HMWP_Classes_Tools::getOption('brute_captcha_secret_key') <> '') { ?>
                                    <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3">
                                        <button type="button" class="btn btn-lg btn-default brute_recaptcha_test hmwp_modal" data-remote="<?php echo site_url('wp-login.php') ?>" data-target="#brute_recaptcha_modal" ><?php echo esc_html__('reCAPTCHA V2 Test', 'hide-my-wp'); ?></button>

                                        <h4 class="mt-5 mb-3"><?php echo esc_html__('Next Steps', 'hide-my-wp'); ?></h4>
                                        <ol>
                                            <li><?php echo sprintf(esc_html__("Run %sreCAPTCHA Test%s and login inside the popup.", 'hide-my-wp'), '<strong>', '</strong>'); ?></li>
                                            <li><?php echo esc_html__("If you're able to login, you've set reCAPTCHA correctly.", 'hide-my-wp'); ?></li>
                                            <li><?php echo esc_html__('If the reCAPTCHA displays any error, please make sure you fix them before moving forward.', 'hide-my-wp'); ?></li>
                                            <li><?php echo esc_html__('Do not logout from your account until you are confident that reCAPTCHA is working and you will be able to login again.', 'hide-my-wp'); ?></li>
                                            <li><?php echo esc_html__("If you can't configure reCAPTCHA, switch to Math reCaptcha protection.", 'hide-my-wp'); ?></li>
                                        </ol>
                                    </div>
                                <?php } ?>

                            </div>
                            <div class="brute_use_captcha_v3" <?php echo(!HMWP_Classes_Tools::getOption('brute_use_captcha_v3') ? 'style="display:none"' : '') ?>>
                                <div class="col-sm-12 text-center border-bottom border-light py-3 mx-0 my-3">
                                    <?php echo sprintf(esc_html__("%sClick here%s to create or view keys for Google reCAPTCHA v3.", 'hide-my-wp'), '<a href="https://www.google.com/recaptcha/admin/create" class="mx-1 text-link font-weight-bold text-uppercase" target="_blank">', '</a>'); ?>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Site key', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo sprintf(esc_html__("Site keys for %sGoogle reCaptcha%s.", 'hide-my-wp'), '<a href="https://www.google.com/recaptcha/admin#list" class="text-link" target="_blank">', '</a>'); ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group">
                                        <input type="text" class="form-control bg-input" name="brute_captcha_site_key_v3" value="<?php echo HMWP_Classes_Tools::getOption('brute_captcha_site_key_v3') ?>"/>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Secret Key', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo sprintf(esc_html__("Secret keys for %sGoogle reCAPTCHA%s.", 'hide-my-wp'), '<a href="https://www.google.com/recaptcha/admin#list" class="text-link" target="_blank">', '</a>'); ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group">
                                        <input type="text" class="form-control bg-input" name="brute_captcha_secret_key_v3" value="<?php echo HMWP_Classes_Tools::getOption('brute_captcha_secret_key_v3') ?>"/>
                                    </div>
                                </div>

                                <?php if (HMWP_Classes_Tools::getOption('brute_captcha_site_key_v3') <> '' && HMWP_Classes_Tools::getOption('brute_captcha_secret_key_v3') <> '') { ?>
                                    <div class="col-sm-12 border-bottom border-light py-3 mx-0 my-3">
                                        <button type="button" class="btn btn-lg btn-default brute_recaptcha_test hmwp_modal" data-remote="<?php echo site_url('wp-login.php') ?>" data-target="#brute_recaptcha_modal" ><?php echo esc_html__('reCAPTCHA V3 Test', 'hide-my-wp'); ?></button>

                                        <h4 class="mt-5 mb-3"><?php echo esc_html__('Next Steps', 'hide-my-wp'); ?></h4>
                                        <ol>
                                            <li><?php echo sprintf(esc_html__("Run %sreCAPTCHA Test%s and login inside the popup.", 'hide-my-wp'), '<strong>', '</strong>'); ?></li>
                                            <li><?php echo esc_html__("If you're able to login, you've set reCAPTCHA correctly.", 'hide-my-wp'); ?></li>
                                            <li><?php echo esc_html__('If the reCAPTCHA displays any error, please make sure you fix them before moving forward.', 'hide-my-wp'); ?></li>
                                            <li><?php echo esc_html__('Do not logout from your account until you are confident that reCAPTCHA is working and you will be able to login again.', 'hide-my-wp'); ?></li>
                                            <li><?php echo esc_html__("If you can't configure reCAPTCHA, switch to Math reCaptcha protection.", 'hide-my-wp'); ?></li>
                                        </ol>
                                    </div>
                                <?php } ?>

                            </div>
                            <div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Max fail attempts', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__('Block IP on login page', 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-md-2 p-0 input-group">
                                        <input type="text" class="form-control bg-input" name="brute_max_attempts" value="<?php echo HMWP_Classes_Tools::getOption('brute_max_attempts') ?>"/>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Ban duration', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__('No. of seconds', 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-md-2 p-0 input-group input-group">
                                        <input type="text" class="form-control bg-input" name="brute_max_timeout" value="<?php echo HMWP_Classes_Tools::getOption('brute_max_timeout') ?>"/>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Lockout Message', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__('Show message instead of login form', 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group input-group">
                                        <textarea type="text" class="form-control bg-input" name="hmwp_brute_message" style="height: 80px"><?php echo HMWP_Classes_Tools::getOption('hmwp_brute_message') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="modal" id="brute_recaptcha_modal" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalLabel"><?php echo esc_html__('reCAPTCHA Test', 'hide-my-wp'); ?></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <iframe class="modal-body" style="min-height: 500px;"></iframe>
                                    </div>
                                </div>
                            </div>

                            <div class="border-top">
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Whitelist IPs', 'hide-my-wp'); ?>:
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/#whitelist_ip_address') ?>" target="_blank" class="d-inline-block ml-2" ><i class="dashicons dashicons-editor-help"></i></a>
                                        <div class="small text-black-50"><?php echo sprintf(esc_html__('You can white-list a single IP like 192.168.0.1 or a range of 245 IPs like 192.168.0.*. Find your IP with %s', 'hide-my-wp'), '<a href="https://whatismyipaddress.com/" target="_blank">https://whatismyipaddress.com/</a>') ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group input-group">
                                        <?php
                                        $ips = array();
                                        if (HMWP_Classes_Tools::getOption('whitelist_ip')) {
                                            $ips = json_decode(HMWP_Classes_Tools::getOption('whitelist_ip'), true);
                                        }
                                        ?>
                                        <textarea type="text" class="form-control bg-input" name="whitelist_ip" style="height: 100px"><?php echo(!empty($ips) ? implode(PHP_EOL, $ips) : '') ?></textarea>
                                    </div>
                                </div>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-md-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Ban IPs', 'hide-my-wp'); ?>:
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/brute-force-attack-protection/#ban_ip_address') ?>" target="_blank" class="d-inline-block ml-2" ><i class="dashicons dashicons-editor-help"></i></a>
                                        <div class="small text-black-50"><?php echo esc_html__('You can ban a single IP like 192.168.0.1 or a range of 245 IPs like 192.168.0.*. These IPs will not be able to access the login page.', 'hide-my-wp') ?></div>
                                    </div>
                                    <div class="col-md-8 p-0 input-group input-group">
                                        <?php
                                        $ips = array();
                                        if (HMWP_Classes_Tools::getOption('banlist_ip')) {
                                            $ips = json_decode(HMWP_Classes_Tools::getOption('banlist_ip'), true);
                                        }
                                        ?>
                                        <textarea type="text" class="form-control bg-input" name="banlist_ip" style="height: 100px"><?php echo(!empty($ips) ? implode(PHP_EOL, $ips) : '') ?></textarea>
                                    </div>
                                </div>
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
            <div class="card col-sm-12 m-0 p-0 rounded-0">
                <div class="card-body f-gray-dark text-left">
                    <h3 class="card-title"><?php echo esc_html__('Brute Force Login Protection', 'hide-my-wp'); ?></h3>
                    <div class="text-info"><?php echo sprintf(esc_html__("Protects your website against Brute Force login attacks using %s A common threat web developers face is a password-guessing attack known as a Brute Force attack. A Brute Force attack is an attempt to discover a password by systematically trying every possible combination of letters, numbers, and symbols until you discover the one correct combination that works.", 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name') . '<br><br>'); ?>
                    </div>
                </div>
            </div>
            <div class="card col-sm-12 p-0">
                <div class="card-body f-gray-dark text-left border-bottom">
                    <h3 class="card-title"><?php echo esc_html__('Features', 'hide-my-wp'); ?></h3>
                    <ul class="text-info" style="margin-left: 16px; list-style: circle;">
                        <li><?php echo esc_html__("Limit the number of allowed login attempts using normal login form.", 'hide-my-wp'); ?></li>
                        <li><?php echo esc_html__("Math & Google reCaptcha verification while logging in.", 'hide-my-wp'); ?></li>
                        <li><?php echo esc_html__("Manually block/unblock IP addresses.", 'hide-my-wp'); ?></li>
                        <li><?php echo esc_html__("Manually whitelist trusted IP addresses.", 'hide-my-wp'); ?></li>
                        <li><?php echo esc_html__("Option to inform user about remaining attempts on login page.", 'hide-my-wp'); ?></li>
                        <li><?php echo esc_html__("Custom message to show to blocked users.", 'hide-my-wp'); ?></li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
