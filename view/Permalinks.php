<?php if(!isset($view)) return; ?>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
    <?php echo $view->getAdminTabs(HMWP_Classes_Tools::getValue('page', 'hmwp_permalinks')); ?>
    <style>#hmwp_wrap .hmwp_nav .hmwp_nav_item:nth-child(n+3){display: none}</style>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <?php do_action('hmwp_notices'); ?>
        <div class="hmwp_col flex-grow-1 px-3 py-3 mr-2 mb-3 bg-white">
            <?php
            //Check the frontend new paths and login
            $view->show('blocks/FrontendLoginCheck');

            //Download the new paths once they are confirmed
            if(HMWP_Classes_Tools::getOption('download_settings') ) { ?>
                <form id="hmwp_download_settings" class="ajax_submit" method="POST">
                    <?php wp_nonce_field('hmwp_download_settings', 'hmwp_nonce') ?>
                    <input type="hidden" name="action" value="hmwp_download_settings"/>
                </form>
                <script>setTimeout(function(){jQuery('#hmwp_download_settings').submit();},1000)</script>
            <?php }?>

            <form method="POST">
                <?php wp_nonce_field('hmwp_settings', 'hmwp_nonce'); ?>
                <input type="hidden" name="action" value="hmwp_settings"/>
                <input type="hidden" name="hmwp_mode" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_mode') ?>"/>

                <?php do_action('hmwp_form_notices'); ?>
                <div id="level" class="card col-sm-12 p-0 m-0 tab-panel tab-panel-first border-0">
                    <div class="card col-sm-12 p-0 m-0">
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Levels of security', 'hide-my-wp'); ?>
                            <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-my-wp-ghost-tutorial/#safeghostmode') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body p-2 text-center">
                            <noscript>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="default_mode" name="hmwp_mode" value="default" class="custom-control-input" <?php echo((HMWP_Classes_Tools::getOption('hmwp_mode') == 'default') ? 'checked="checked"' : '') ?>>
                                    <label class="custom-control-label" for="default_mode"><?php echo esc_html__("Deactivated", 'hide-my-wp') ?></label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="lite_mode" name="hmwp_mode" value="lite" class="custom-control-input" <?php echo((HMWP_Classes_Tools::getOption('hmwp_mode') == 'lite') ? 'checked="checked"' : '') ?>>
                                    <label class="custom-control-label" for="lite_mode"><?php echo esc_html__("Lite mode", 'hide-my-wp') ?></label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="ninja_mode" name="hmwp_mode" value="ninja" class="custom-control-input">
                                    <label class="custom-control-label" for="ninja_mode"><?php echo esc_html__("Ghost mode", 'hide-my-wp') ?></label>
                                </div>
                                <style>.group_autoload{display: none !important;}</style>
                                <style>#hmwp_wrap .hmwp_nav .hmwp_nav_item:nth-child(n+3){display: block}#hmwp_wrap .tab-panel:not(.tab-panel-first){display: block}</style>
                            </noscript>
                            <div class="group_autoload d-flex justify-content-center btn-group btn-group-lg mt-3" role="group" >
                                <button type="button" class="btn btn-outline-info default_autoload m-1 py-4 px-4 <?php echo((HMWP_Classes_Tools::getOption('hmwp_mode') == 'default') ? 'active' : '') ?>"><?php echo esc_html__("Deactivated", 'hide-my-wp') ?></button>
                                <button type="button" class="btn btn-outline-info lite_autoload m-1 py-4 px-4 hmwp_modal <?php echo((HMWP_Classes_Tools::getOption('hmwp_mode') == 'lite') ? 'active' : '') ?>" onclick="jQuery('#hmwp_safe_mode_modal').modal('show')"><?php echo esc_html__("Lite mode", 'hide-my-wp') ?></button>
                                <button type="button" class="btn btn-outline-info ninja_autoload m-1 py-4 px-4 hmwp_modal" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')"><?php echo esc_html__("Ghost mode", 'hide-my-wp') ?></button>
                                <div class="box"><div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div></div>
                            </div>

                            <script>
                                (function ($) {

                                    $(document).ready(function () {

                                        $(".default_autoload").on('click', function () {
                                            $('input[name=hmwp_mode]').val('default');
                                            $('.group_autoload button').removeClass('active');
                                            <?php
                                            foreach (HMWP_Classes_Tools::$default as $name => $value) {
                                                if (is_string($value) && $value <> "0" && $value <> "1") {
                                                    echo '$("input[type=text][name=' . $name . ']").val("' . str_replace('"', '\\"', $value) . '");' . "\n";
                                                } elseif ($value == "0" || $value == "1") {
                                                    echo '$("input[name=' . $name . ']").prop("checked", ' . (int)$value . '); $("input[name=' . $name . ']").trigger("change");';
                                                }
                                            }
                                            ?>
                                            $('input[name=hmwp_admin_url]').trigger('keyup');
                                            $('.hmwp_nav_item').not(':first').hide();

                                            $('.tab-panel_tutorial').show();
                                            $('.hmwp_emulate_cms').hide();
                                            $('.hmwp_disable_url').hide();
                                        });

                                        $(".safe_confirmation").on('click', function () {
                                            $('input[name=hmwp_mode]').val('lite');
                                            <?php
                                            $lite = @array_merge(HMWP_Classes_Tools::$default, HMWP_Classes_Tools::$lite);
                                            foreach ($lite as $name => $value) {
                                                if (is_string($value) && $value <> "0" && $value <> "1") {
                                                    echo '$("input[type=text][name=' . $name . ']").val("' . str_replace('"', '\\"', $value) . '");' . "\n";
                                                } elseif ($value == "0" || $value == "1") {
                                                    echo '$("input[name=' . $name . ']").prop("checked", ' . (int)$value . '); $("input[name=' . $name . ']").trigger("change");';

                                                }
                                            }
                                            ?>
                                            $('input[name=hmwp_admin_url]').trigger('keyup');
                                            $('.tab-panel_tutorial').hide();
                                            $('.hmwp_nav_item').show();

                                            $('.hmwp_emulate_cms').show();
                                            $('.hmwp_disable_url').hide();
                                            $('select[name="hmwp_emulate_cms"] option[value="<?php echo esc_attr($lite['hmwp_emulate_cms']) ?>"]').prop('selected', 'selected');
                                        });

                                        if ($('input[name=hmwp_mode]').val() == 'default'){
                                            $('.hmwp_nav_item').not(':first').hide();
                                        }else{
                                            $('.hmwp_nav_item').show();
                                        }

                                        //Listen the modal close
                                        $(document).on('hide.bs.modal','.modal', function () {
                                            $('.group_autoload button').removeClass('active');
                                            $('.group_autoload .'+$('input[name=hmwp_mode]').val()+'_autoload').addClass('active');
                                        });
                                    });
                                })(jQuery);
                            </script>

                            <div class="hmwp_disable_url col-sm-12 row border-bottom border-light py-2 m-0">
                                <?php if(!HMWP_Classes_Tools::getOption('logout')) { ?>
                                    <?php if (defined('HMWP_DEFAULT_LOGIN') && HMWP_DEFAULT_LOGIN ) {
                                        if(stripos(HMWP_DEFAULT_LOGIN,home_url()) !== false){
                                            $custom_login = HMWP_DEFAULT_LOGIN;
                                        }else{
                                            $custom_login = home_url(HMWP_DEFAULT_LOGIN);
                                        }
                                        ?>
                                        <div class="col-sm-12 pt-3">
                                            <strong><?php echo  esc_html__("Login URL", 'hide-my-wp') ?>:</strong>
                                            <?php echo '<a href="' . esc_url($custom_login) . '" target="_blank">' . esc_url($custom_login) . '</a>' ?>
                                        </div>
                                    <?php }else{ ?>
                                        <div class="col-sm-12 pt-3">
                                            <strong><?php echo  esc_html__("Login URL", 'hide-my-wp') ?>:</strong>
                                            <?php echo '<a href="' . site_url() . '/' . HMWP_Classes_Tools::getOption('hmwp_login_url') . '" target="_blank">' . site_url() . '/' . HMWP_Classes_Tools::getOption('hmwp_login_url') . '</a>' ?>
                                        </div>
                                    <?php }?>
                                <?php }?>
                            </div>
                        </div>
                        <div class="hmwp_emulate_cms col-sm-12 row justify-content-center py-4 shadow-none" <?php echo((HMWP_Classes_Tools::getOption('hmwp_mode') == 'default') ? 'style="display:none"' : '') ?>>
                            <div class="py-2 px-1 text-right">
                                <div class="font-weight-bold"><?php echo esc_html__('Simulate CMS', 'hide-my-wp'); ?>:</div>
                            </div>
                            <div class="col-sm-4 px-2 input-group">
                                <select name="hmwp_emulate_cms" class="form-control bg-input mb-1">
                                    <option value="" <?php selected('', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?>><?php echo esc_html__("No CMS Simulation", 'hide-my-wp') ?></option>
                                    <option value="drupal" <?php selected('drupal', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Drupal 8", 'hide-my-wp') ?></option>
                                    <option value="drupal9" <?php selected('drupal9', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Drupal 9", 'hide-my-wp') ?></option>
                                    <option value="drupal10" <?php selected('drupal10', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Drupal 10", 'hide-my-wp') ?></option>
                                    <option value="joomla3" <?php selected('joomla3', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Joomla 3", 'hide-my-wp') ?></option>
                                    <option value="joomla4" <?php selected('joomla4', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Joomla 4", 'hide-my-wp') ?></option>
                                    <option value="joomla5" <?php selected('joomla5', HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) ?> ><?php echo esc_html__("Joomla 5", 'hide-my-wp') ?></option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="card col-sm-12 p-0 m-0 mt-3" <?php echo((HMWP_Classes_Tools::getOption('hmwp_mode') == 'default') ? 'style="display:none"' : '') ?>>
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Whitelist IPs', 'hide-my-wp'); ?>
                            <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-geo-security/#whitelisting') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                        </h3>
                        <div class="card-body">
                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-md-4 p-0 font-weight-bold">
					                <?php echo esc_html__('Whitelist IPs', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__('Add IPs that can pass plugin security', 'hide-my-wp') ?></div>
                                </div>
                                <div class="col-md-8 p-0 input-group input-group">
					                <?php
					                $ips = array();
					                if (HMWP_Classes_Tools::getOption('whitelist_ip')) {
						                $ips = json_decode(HMWP_Classes_Tools::getOption('whitelist_ip'), true);
					                }
					                ?>
                                    <textarea type="text" class="form-control bg-input" name="whitelist_ip" style="height: 100px"><?php echo(!empty($ips) ? implode(PHP_EOL, $ips) : '') ?></textarea>
                                    <div class="small text-black-50 col-md-12 py-2 px-0"><?php echo sprintf(esc_html__('You can white-list a single IP address like 192.168.0.1 or a range of 245 IPs like 192.168.0.*. Find your IP with %s', 'hide-my-wp'), '<a href="https://whatismyipaddress.com/" target="_blank">https://whatismyipaddress.com/</a>') ?></div>
                                </div>
                            </div>

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-md-4 p-0 font-weight-bold">
                                    <?php echo esc_html__('Whitelist Paths', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__('Add paths that can pass plugin security', 'hide-my-wp') ?></div>
                                    <div class="small text-black-50"><?php echo esc_html__('e.g. /cart/', 'hide-my-wp') ?></div>
                                    <div class="small text-black-50"><?php echo esc_html__('e.g. /checkout/', 'hide-my-wp') ?></div>
                                </div>
                                <div class="col-md-8 p-0 input-group input-group">
                                    <?php
                                    $urls = array();
                                    if (HMWP_Classes_Tools::getOption('whitelist_urls')) {
                                        $urls = json_decode(HMWP_Classes_Tools::getOption('whitelist_urls'), true);
                                    }
                                    ?>
                                    <textarea type="text" class="form-control bg-input" name="whitelist_urls" style="height: 100px"><?php echo(!empty($urls) ? implode(PHP_EOL, $urls) : '') ?></textarea>
                                </div>
                            </div>

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3 hmwp_sqlinjection border-bottom">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__('Whitelist Options', 'hide-my-wp'); ?></div>
                                    <div class="text-black-50 small"><?php echo esc_html__('Chose what to do when accessing from whitelist IP addresses and whitelisted paths.', 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group mb-1">
                                    <select name="whitelist_level" class="form-control bg-input">
                                        <option value="0" <?php echo selected(0, HMWP_Classes_Tools::getOption('whitelist_level')) ?> ><?php echo esc_html__('Allow Hidden Paths', 'hide-my-wp'); ?></option>
                                        <option value="1" <?php echo selected(1, HMWP_Classes_Tools::getOption('whitelist_level')) ?> ><?php echo esc_html__('Show Default Paths & Allow Hidden Paths', 'hide-my-wp'); ?></option>
                                        <option value="2" <?php echo selected(2, HMWP_Classes_Tools::getOption('whitelist_level')) ?> ><?php echo esc_html__('Show Defaults Paths & Allow Everything', 'hide-my-wp'); ?></option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="card col-sm-12 p-0 m-0 mt-3" >
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Help & FAQs', 'hide-my-wp'); ?></h3>
                        <div class="card-body">
                            <?php if(HMWP_Classes_Tools::isNginx()) { ?>
                                <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-setup-hide-my-wp-on-nginx-server/') ?>" target="_blank">Setup The Plugin On Nginx Server</a></div>
                                <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-configure-hide-my-wp-on-nginx-web-server-with-virtual-private-server/') ?>" target="_blank">Setup The Plugin On Nginx Server with Virtual Private Server</a></div>
                                <div class="border-bottom my-3"></div>
                            <?php }?>
                            <?php if(HMWP_Classes_Tools::isWpengine()) { ?>
                                <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/hide-my-wp-pro-compatible-with-wp-engine/') ?>" target="_blank">Setup The Plugin On WP Engine</a></div>
                                <div class="border-bottom my-3"></div>
                            <?php }?>
                            <?php if(HMWP_Classes_Tools::isGodaddy()) { ?>
                                <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-use-hide-my-wp-with-godaddy/') ?>" target="_blank">Setup The Plugin On Godaddy</a></div>
                                <div class="border-bottom my-3"></div>
                            <?php }?>
                            <?php if(HMWP_Classes_Tools::isIIS()) { ?>
                                <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/setup-hide-my-wp-on-windows-iis-server/') ?>" target="_blank">Setup The Plugin On Windows IIS Server</a></div>
                                <div class="border-bottom my-3"></div>
                            <?php }?>
                            <?php if(HMWP_Classes_Tools::isInmotion()) { ?>
                                <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/hide-my-wp-pro-compatible-with-inmotion-wordpress-hosting/') ?>" target="_blank">Setup The Plugin On Inmotion Server</a></div>
                                <div class="border-bottom my-3"></div>
                            <?php }?>

                            <div class="mb-2 text-success font-weight-bold"><i class="dashicons dashicons-editor-help"></i><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/hide-my-wp-ghost-tutorial/#safeghostmode') ?>" target="_blank">STEP BY STEP PLUGIN SETUP</a><i class="dashicons dashicons-editor-help"></i></div>
                            <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-hide-from-wordpress-theme-detectors/') ?>" target="_blank">How To Hide Your Site From Detectors & Hackers Bots</a></div>
                            <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-activate-brute-force-protection/') ?>" target="_blank">How To Use Brute Force Protection</a></div>
                            <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-use-website-events-log/') ?>" target="_blank">How To Use Events Log</a></div>

                            <div class="border-bottom my-3"></div>
                            <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/hide-wordpress-website-from-theme-detectors-or-against-hackers/') ?>" target="_blank">Hide WordPress from Theme Detectors or from Hackers Bots?</a></div>
                            <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/theme-not-loading-correctly-website-loads-slower/') ?>" target="_blank">Theme Not Loading Correctly & Website Loads Slower</a></div>


                            <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/hide-my-wp-compatibility-plugins-list/') ?>" target="_blank">Compatibility Plugins List</a></div>
                            <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/knowledge-base/') ?>" target="_blank"><?php echo esc_html__('More Help'); ?>>></a></div>
                        </div>
                    </div>

                    <div class="card col-sm-12 p-0 m-0 mt-4" >
                        <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Troubleshooting', 'hide-my-wp'); ?></h3>
                        <div class="card-body">

                            <h6 class="mb-2">In case your configs are wrong: </h6>
                            <ul style="margin: 0;padding: 0;list-style: initial;">
                                <li style="margin: 0 0 0 40px;padding: 0;line-height: 30px;">
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/theme-not-loading-correctly-website-loads-slower/') ?>" target="_blank">Theme Detection Problems</a>
                                </li>
                                <li style="margin: 0 0 0 40px;padding: 0;line-height: 30px;">
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/theme-not-loading-correctly-website-loads-slower/') ?>" target="_blank">Loading and Website Speed</a>
                                </li>
                                <li style="margin: 0 0 0 40px;padding: 0;line-height: 30px;">
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/hiding-plugins-like-woocommerce-and-elementor/') ?>" target="_blank">Hiding Classes with Text Mapping Problems</a>
                                </li>
                                <li style="margin: 0 0 0 40px;padding: 0;line-height: 30px;">
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/hide-my-wp-how-to-disable-the-lugin-in-case-of-error/#solution2') ?>" target="_blank">Remove Plugin Through File Manager</a>
                                </li>
                            </ul>


                            <div class="mt-3" <?php echo((HMWP_Classes_Tools::getOption('hmwp_mode') == 'default') ? 'style="display:none"' : '') ?>>
                                <h6 class="mb-2"><?php echo sprintf(esc_html__("Copy the %s SAFE URL %s and use it to deactivate all the custom paths if you can't login.", 'hide-my-wp'), '<strong><a href="'. esc_url(site_url() . "/wp-login.php?" . HMWP_Classes_Tools::getOption('hmwp_disable_name') . "=" . HMWP_Classes_Tools::getOption('hmwp_disable')) .'" class="text-danger" target="_blank">', '</a></strong>'); ?></h6>
                                <h6><a href="<?php echo esc_url(site_url() . "/wp-login.php?" . HMWP_Classes_Tools::getOption('hmwp_disable_name') . "=" . HMWP_Classes_Tools::getOption('hmwp_disable'))?>" target="_blank"><?php echo esc_url(site_url() . "/wp-login.php?" . HMWP_Classes_Tools::getOption('hmwp_disable_name') . "=" . HMWP_Classes_Tools::getOption('hmwp_disable'))?></a></h6>
                            </div>

                        </div>
                    </div>

                </div>

                <div id="newadmin" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Admin Security', 'hide-my-wp'); ?>
                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/how-to-change-and-hide-wp-admin-path-with-hide-my-wp-ghost-plugin/') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body">
                        <?php if (defined('HMWP_DEFAULT_ADMIN') && HMWP_DEFAULT_ADMIN && HMW_RULES_IN_CONFIG ) {
                            echo ' <div class="text-danger col-sm-12 border-bottom border-light py-3 mx-0 my-3">' . sprintf(esc_html__('Your admin URL is changed by another plugin/theme in %s. To activate this option, disable the custom admin in the other plugin or deativate it.', 'hide-my-wp'), '<strong>' . HMWP_DEFAULT_ADMIN . '</strong>') . '</div>';
                            echo '<input type="hidden" name="hmwp_admin_url" value="' . HMWP_Classes_Tools::$default['hmwp_admin_url'] . '"/>';
                        } else {
                            if (HMWP_Classes_Tools::isGodaddy() ) {
                                echo ' <div class="text-danger col-sm-12 border-bottom border-light py-3 mx-0 my-3">' . sprintf(esc_html__("Your admin URL can't be changed on %s hosting because of the %s security terms.", 'hide-my-wp'), '<strong>Godaddy</strong>', '<strong>Godaddy</strong>') . '</div>';
                                echo '<input type="hidden" name="hmwp_admin_url" value="' . HMWP_Classes_Tools::$default['hmwp_admin_url'] . '"/>';
                            } elseif (HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->isConfigAdminCookie() ) {
                                echo ' <div class="text-danger col-sm-12 border-bottom border-light py-3 mx-0 my-3">' . sprintf(esc_html__("The constant ADMIN_COOKIE_PATH is defined in wp-config.php by another plugin. You can't change %s unless you remove the line define('ADMIN_COOKIE_PATH', ...);.", 'hide-my-wp'), '<strong>' . HMWP_Classes_Tools::$default['hmwp_admin_url'] . '</strong>') . '</div>';
                                echo '<input type="hidden" name="hmwp_admin_url" value="' . HMWP_Classes_Tools::$default['hmwp_admin_url'] . '"/>';
                            } else {
                                ?>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Custom Admin Path', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__('eg. adm, back', 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_admin_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_admin_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_admin_url'] ?>"/>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_admin') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_admin" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_admin" name="hmwp_hide_admin" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_admin') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_admin"><?php echo esc_html__('Hide "wp-admin"', 'hide-my-wp'); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__('Hide /wp-admin path from visitors.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_admin_loggedusers" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_admin_loggedusers" name="hmwp_hide_admin_loggedusers" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_admin_loggedusers') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_admin_loggedusers"><?php echo esc_html__('Hide "wp-admin" From Non-Admin Users', 'hide-my-wp'); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__('Hide /wp-admin path from non-administrator users.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hide_newadmin_div" <?php echo(HMWP_Classes_Tools::getOption('hmwp_admin_url') == HMWP_Classes_Tools::$default['hmwp_admin_url'] ? 'style="display:none;"' : '') ?>>
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_newadmin" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_newadmin" name="hmwp_hide_newadmin" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_newadmin') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_newadmin"><?php echo esc_html__('Hide the New Admin Path', 'hide-my-wp'); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__('Hide the new admin path from visitors. Show the new admin path only for logged users.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="admin_warning col-sm-12 my-3 text-danger p-0 text-center small" style="display: none">
                                <?php echo esc_html__("Some themes don't work with custom Admin and Ajax paths. In case of ajax errors, switch back to wp-admin and admin-ajax.php.", 'hide-my-wp'); ?>
                            </div>
                            <div class="col-sm-12 text-center border-light py-1 m-0">
                                <a href="<?php echo HMWP_Classes_Tools::getSettingsUrl('hmwp_tweaks#tab=redirects', true) ?>" target="_blank">
                                    <?php echo esc_html__('Manage Login and Logout Redirects', 'hide-my-wp'); ?>
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div id="newlogin" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Login Security', 'hide-my-wp'); ?>
                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/change-wordpress-login-path-with-hide-my-wp-ghost-plugin/') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body">
                        <?php if (defined('HMWP_DEFAULT_LOGIN') && HMWP_DEFAULT_LOGIN ) {
                            echo '<div class="text-danger col-sm-12 border-bottom border-light py-3 mx-0 my-3">' . sprintf(esc_html__('Your login URL is changed by another plugin/theme in %s. To activate this option, disable the custom login in the other plugin or deativate it.', 'hide-my-wp'), '<strong>' . HMWP_DEFAULT_LOGIN . '</strong>') . '</div>';
                            echo '<input type="hidden" name="hmwp_login_url" value="' . HMWP_Classes_Tools::$default['hmwp_login_url'] . '"/>';
	                        echo '<input type="hidden" name="hmwp_lostpassword_url" value=""/>';
	                        echo '<input type="hidden" name="hmwp_register_url" value=""/>';
	                        echo '<input type="hidden" name="hmwp_logout_url" value=""/>';
	                        echo '<input type="hidden" name="hmwp_activate_url" value=""/>';
                            ?>

                            <div class="col-sm-12 row mb-1 ml-1 p-2" <?php echo(HMWP_DEFAULT_LOGIN == HMWP_Classes_Tools::$default['hmwp_login_url'] ? 'style="display:none;"' : '') ?>>
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_wplogin" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_wplogin" name="hmwp_hide_wplogin" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_wplogin') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_wplogin"><?php echo esc_html__('Hide "wp-login.php"', 'hide-my-wp'); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__('Hide /wp-login.php path from visitors.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if(HMWP_DEFAULT_LOGIN == HMWP_Classes_Tools::getDefault('hmwp_login_url') || HMWP_DEFAULT_LOGIN == 'login'){ ?>
                                <input type="hidden" name="hmwp_hide_login" value="0"/>
                            <?php }else{ ?>
                                <div class="col-sm-12 row mb-1 ml-1 p-2" <?php echo(HMWP_DEFAULT_LOGIN == HMWP_Classes_Tools::getDefault('hmwp_login_url') || HMWP_DEFAULT_LOGIN == 'login' ? 'style="display:none;"' : '') ?>>
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_login" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_login" name="hmwp_hide_login" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_login') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_hide_login"><?php echo esc_html__('Hide "login" Path', 'hide-my-wp'); ?></label>
                                            <div class="text-black-50 ml-5"><?php echo esc_html__('Hide /login path from visitors.', 'hide-my-wp'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>

                            <div class="col-sm-12 row mb-1 ml-1 p-2" <?php echo(HMWP_DEFAULT_LOGIN == HMWP_Classes_Tools::$default['hmwp_login_url'] || HMWP_DEFAULT_LOGIN == 'login' ? 'style="display:none;"' : '') ?>>
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_newlogin" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_newlogin" name="hmwp_hide_newlogin" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_newlogin') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_newlogin"><?php echo esc_html__('Hide the New Login Path', 'hide-my-wp'); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__('Hide the new login path from visitors. Show the new login path only for direct access.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                        <?php } else { ?>
                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-4 p-0 font-weight-bold">
                                    <?php echo esc_html__('Custom Login Path', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__('eg. login or signin', 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_login_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_login_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_login_url'] ?>"/>
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_login') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hide_wplogin_div" <?php echo(HMWP_Classes_Tools::getOption('hmwp_login_url') == HMWP_Classes_Tools::$default['hmwp_login_url'] ? 'style="display:none;"' : '') ?>>
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_wplogin" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_wplogin" name="hmwp_hide_wplogin" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_wplogin') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_wplogin"><?php echo esc_html__('Hide "wp-login.php"', 'hide-my-wp'); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__('Hide /wp-login.php path from visitors.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hide_login_div" <?php echo(HMWP_Classes_Tools::getOption('hmwp_login_url') == HMWP_Classes_Tools::$default['hmwp_login_url'] ? 'style="display:none;"' : '') ?>>
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_login" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_login" name="hmwp_hide_login" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_login') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_login"><?php echo esc_html__('Hide "login" Path', 'hide-my-wp'); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__('Hide /login path from visitors.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hide_newlogin_div" <?php echo(HMWP_Classes_Tools::getOption('hmwp_login_url') == HMWP_Classes_Tools::$default['hmwp_login_url'] ? 'style="display:none;"' : '') ?>>
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_newlogin" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_newlogin" name="hmwp_hide_newlogin" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_newlogin') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_newlogin"><?php echo esc_html__('Hide the New Login Path', 'hide-my-wp'); ?></label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__('Hide the new login path from visitors. Show the new login path only for direct access.', 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php
                            if(function_exists('get_available_languages')) {
                                $languages = get_available_languages();
                                if (!empty($languages)) {
                                    ?>
                                    <div class="col-sm-12 row mb-1 ml-1 p-2">
                                        <div class="checker col-sm-12 row my-2 py-1">
                                            <div class="col-sm-12 p-0 switch switch-sm">
                                                <input type="hidden" name="hmwp_disable_language_switcher" value="0"/>
                                                <input type="checkbox" id="hmwp_disable_language_switcher"
                                                       name="hmwp_disable_language_switcher"
                                                       class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_language_switcher') ? 'checked="checked"' : '') ?>
                                                       value="1"/>
                                                <label for="hmwp_disable_language_switcher"><?php echo esc_html__('Hide Language Switcher', 'hide-my-wp'); ?></label>
                                                <div class="text-black-50 ml-5"><?php echo esc_html__("Hide the language switcher option on the login page", 'hide-my-wp'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php }
                            }?>

                            <div class="border-bottom border-gray"></div>

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-4 p-0 font-weight-bold" style="font-size: 0.9rem">
                                    <?php echo esc_html__('Custom Lost Password Path', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__('eg. lostpass or forgotpass', 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_lostpassword_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_lostpassword_url') ?>" placeholder="?action=lostpassword"/>
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_lost_password') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                </div>
                            </div>

                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-4 p-0 font-weight-bold">
                                    <?php echo esc_html__('Custom Register Path', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__('eg. newuser or register', 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_register_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_register_url') ?>" placeholder="?action=register"/>
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_register') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                </div>
                            </div>


                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-4 p-0 font-weight-bold">
                                    <?php echo esc_html__('Custom Logout Path', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__('eg. logout or disconnect', 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_logout_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_logout_url') ?>" placeholder="?action=logout"/>
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_logout') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                </div>
                            </div>

                            <?php if (HMWP_Classes_Tools::isMultisites() ) { ?>
                                <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                    <div class="col-sm-4 p-0 font-weight-bold">
                                        <?php echo esc_html__('Custom Activation Path', 'hide-my-wp'); ?>:
                                        <div class="small text-black-50"><?php echo esc_html__('eg. multisite activation link', 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_activate_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_activate_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_activate_url'] ?>"/>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_activation') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                    </div>
                                </div>
                            <?php } ?>
	                    <?php } ?>



                        <div class="col-sm-12 text-center border-light py-1 m-0">
                            <a href="<?php echo HMWP_Classes_Tools::getSettingsUrl('hmwp_tweaks#tab=redirects', true) ?>" target="_blank">
                                <?php echo esc_html__('Manage Login and Logout Redirects', 'hide-my-wp'); ?>
                            </a>
                        </div>


                    </div>
                </div>
                <div id="author" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('User Security', 'hide-my-wp'); ?>
                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/how-to-change-author-path-and-hide-id-with-hide-my-wp-ghost-plugin/') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body">

                        <?php if (!HMWP_Classes_Tools::isMultisiteWithPath() && !HMWP_Classes_Tools::isNginx() && !HMWP_Classes_Tools::isWpengine() ) { ?>
                            <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                                <div class="col-sm-4 p-0 font-weight-bold">
                                    <?php echo esc_html__('Custom author Path', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__('eg. profile, usr, writer', 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_author_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_author_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_author_url'] ?>"/>
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_author') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                </div>
                            </div>
                        <?php } else { ?>
                            <input type="hidden" name="hmwp_author_url" value="<?php echo HMWP_Classes_Tools::$default['hmwp_author_url'] ?>"/>
                        <?php } ?>
                        <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_pro">
                            <div class="box" >
                                <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                            </div>
                            <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_authors" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_authors" name="hmwp_hide_authors" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_authors') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_authors"><?php echo esc_html__('Hide Author ID URL', 'hide-my-wp'); ?>
                                            <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_author') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__("Don't let URLs like domain.com?author=1 show the user login name", 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div id="ajax" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Ajax Security', 'hide-my-wp'); ?>
                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/how-to-change-wordpress-admin-ajax-php-path-with-hide-my-wp-ghost-plugin/') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body">
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom admin-ajax Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. ajax, json', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_admin-ajax_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_admin-ajax_url'] ?>"/>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_ajax') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hideajax_admin_div">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hideajax_admin" value="0"/>
                                    <input type="checkbox" id="hmwp_hideajax_admin" name="hmwp_hideajax_admin" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hideajax_admin') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hideajax_admin"><?php echo esc_html__('Hide wp-admin from Ajax URL', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_ajax') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo sprintf(esc_html__('Show /%s instead of /%s', 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url'), HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/' . HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url')); ?></div>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__('(works only with the custom admin-ajax path to avoid infinite loops)', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hideajax_paths" value="0"/>
                                    <input type="checkbox" id="hmwp_hideajax_paths" name="hmwp_hideajax_paths" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hideajax_paths') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hideajax_paths"><?php echo esc_html__('Change Paths in Ajax Calls', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#change_paths_ajax') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__('This will prevent from showing the old paths when an image or font is called through ajax', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="core" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('WP Core Security', 'hide-my-wp'); ?></h3>
                    <div class="card-body">

                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom wp-content Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. core, inc, include', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_wp-content_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_wp-content_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_wp-content_url'] ?>"/>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_wpcontent') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>
                        </div>
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom wp-includes Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. lib, library', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_wp-includes_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_wp-includes_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_wp-includes_url'] ?>"/>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_wpincludes') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>
                        </div>
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">

                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom uploads Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. images, files', 'hide-my-wp'); ?></div>
                            </div>
                            <?php if (!defined('UPLOADS') ) { ?>
                                <div class="col-sm-8 p-0 input-group">
                                    <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_upload_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_upload_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_upload_url'] ?>"/>
                                    <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_uloads') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                </div>
                            <?php } else { ?>
                                <div class="col-sm-8 text-danger p-0">
                                    <?php echo sprintf(esc_html__("You already defined a different wp-content/uploads directory in wp-config.php %s", 'hide-my-wp'), ': <strong>' . UPLOADS . '</strong>'); ?>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom comment Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. comments, discussion', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_wp-comments-post" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_wp-comments-post') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_wp-comments-post'] ?>"/>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_comments') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_pro">
                            <div class="box" >
                                <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                            </div>
                            <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_oldpaths" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_oldpaths" name="hmwp_hide_oldpaths" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_oldpaths') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_oldpaths"><?php echo esc_html__('Hide WordPress Common Paths', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_common_paths') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__('Hide the old /wp-content, /wp-include paths once they are changed with the new ones', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_pro">
                            <div class="box" >
                                <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                            </div>
                            <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                               <div class="col-sm-12 p-0 switch switch-sm ">
                                    <input type="hidden" name="hmwp_hide_commonfiles" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_commonfiles" name="hmwp_hide_commonfiles" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_commonfiles') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_commonfiles"><?php echo esc_html__('Hide WordPress Common Files', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_common_files') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__('Hide wp-config.php , wp-config-sample.php, readme.html, license.txt, upgrade.php and install.php files', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if (HMWP_Classes_Tools::isNginx() || HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) { ?>

                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_pro">
                                <div class="box" >
                                    <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                                </div>
                                <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                                   <div class="col-sm-12 p-0 switch switch-sm">
                                        <?php $uploads = wp_upload_dir() ?>
                                        <input type="hidden" name="hmwp_disable_browsing" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_browsing" name="hmwp_disable_browsing" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_browsing') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_disable_browsing"><?php echo esc_html__('Disable Directory Browsing', 'hide-my-wp'); ?>
                                            <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#disable_browsing') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo sprintf(esc_html__("Don't let hackers see any directory content. See %sUploads Directory%s", 'hide-my-wp'), '<a href="' . $uploads['baseurl'] . '" target="_blank">', '</a>'); ?></div>
                                        <div class="text-danger my-2 ml-5"><?php echo sprintf(esc_html__("Normally, the option to block visitors from browsing server directories is activated by the host through server configuration, and activating it twice in the config file may cause errors, so it's best to first check if the %sUploads Directory%s is visible.", 'hide-my-wp'), '<a href="' . $uploads['baseurl'] . '" target="_blank">', '</a>'); ?></div>
                                   </div>
                                </div>
                            </div>

                        <?php } ?>

                    </div>
                </div>
                <div id="plugin" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Plugins Settings', 'hide-my-wp'); ?>
                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_plugins') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body">
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom plugins Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. modules', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_plugin_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_plugin_url'] ?>"/>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_plugins') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_plugins" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_plugins" name="hmwp_hide_plugins" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_plugins') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_plugins"><?php echo esc_html__('Hide Plugin Names', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_plugins') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__('Give random names to each plugin', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hide_plugins">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_all_plugins" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_all_plugins" name="hmwp_hide_all_plugins" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_all_plugins') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_all_plugins"><?php echo esc_html__('Hide All The Plugins', 'hide-my-wp'); ?></label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__('Hide both active and deactivated plugins', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_pro">
                            <div class="box" >
                                <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                            </div>
                            <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                               <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_oldpaths_plugins" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_oldpaths_plugins" name="hmwp_hide_oldpaths_plugins" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_oldpaths_plugins') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_oldpaths_plugins"><?php echo esc_html__('Hide WordPress Old Plugins Path', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_old_plugin_path') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__("Hide the old /wp-content/plugins path once it's changed with the new one", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="border-top mt-3 pt-3 hmwp_hide_plugins">
                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_pro">
                                <div class="box" >
                                    <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                                </div>
                                <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                                   <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_plugins_advanced" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_plugins_advanced" name="hmwp_hide_plugins_advanced" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_plugins_advanced') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_plugins_advanced"><?php echo esc_html__('Show Advanced Options', 'hide-my-wp'); ?>
                                            <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#plugins_advanced_options') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            <span class="text-black-50 small">(<?php echo esc_html__("not recommended", 'hide-my-wp'); ?>)</span> </label>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__("Manually customize each plugin name and overwrite the random name", 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div id="theme" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Themes Security', 'hide-my-wp'); ?>
                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_themes') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body">
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom themes Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. aspect, templates, styles', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_themes_url" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_themes_url') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_themes_url'] ?>"/>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_themes') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_themes" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_themes" name="hmwp_hide_themes" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_themes') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_themes"><?php echo esc_html__('Hide Theme Names', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_themes') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__('Give random names to each theme (works in WP multisite)', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_pro">
                            <div class="box" >
                                <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                            </div>
                            <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                               <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_oldpaths_themes" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_oldpaths_themes" name="hmwp_hide_oldpaths_themes" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_oldpaths_themes') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_oldpaths_themes"><?php echo esc_html__('Hide WordPress Old Themes Path', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_old_theme_path') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__("Hide the old /wp-content/themes path once it's changed with the new one", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="border-bottom border-gray"></div>

                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom theme style name', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. main.css,  theme.css, design.css', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_themes_style" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_themes_style') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_themes_style'] ?>"/>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#customize_themes_style') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>
                        </div>


                        <div class="border-top mt-3 pt-3 hmwp_hide_themes">
                            <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_pro">
                                <div class="box" >
                                    <div class="ribbon"><span><?php echo esc_html__( 'PRO', 'hide-my-wp' ) ?></span></div>
                                </div>
                                <div class="checker col-sm-12 row my-2 py-1" style="opacity: 0.3" onclick="jQuery('#hmwp_ghost_mode_modal').modal('show')">
                                   <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_themes_advanced" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_themes_advanced" name="hmwp_hide_themes_advanced" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_themes_advanced') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_themes_advanced"><?php echo esc_html__('Show Advanced Options', 'hide-my-wp'); ?>
                                            <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#themes_advanced_options') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            <span class="text-black-50 small">(<?php echo esc_html__("not recommended", 'hide-my-wp'); ?>)</span> </label>
                                        </label>
                                        <div class="text-black-50 ml-5"><?php echo esc_html__("Manually customize each theme name and overwrite the random name", 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <div id="api" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('API Settings', 'hide-my-wp'); ?>
                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_rest_api') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body">
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom wp-json Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. json, api, call', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_wp-json" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_wp-json') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_wp-json'] ?>"/>
                                <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_rest_api') ?>" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>

                            <div class="col-sm-12 mt-2 p-2 alert-danger text-center"><?php echo sprintf(esc_html__("Update the settings on %s to refresh the paths after changing REST API path.", 'hide-my-wp'), '<a href="'.admin_url('options-permalink.php').'">'.esc_html__('Settings') . ' > ' . esc_html__('Permalinks').'</a>'); ?></div>

                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_rest_api" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_rest_api" name="hmwp_hide_rest_api" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_rest_api') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_rest_api"><?php echo esc_html__('Hide REST API URL link', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_rest_api') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__("Hide wp-json & ?rest_route link tag from website header", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                       <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_rest_api" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_rest_api" name="hmwp_disable_rest_api" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_rest_api') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_rest_api"><?php echo esc_html__('Disable REST API access', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_rest_api') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        <span class="text-black-50 small">(<?php echo esc_html__("not recommended", 'hide-my-wp'); ?>)</span> </label>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__("Disable REST API access for not logged in users", 'hide-my-wp'); ?></div>
                                    <div class="text-danger my-2 ml-5"><?php echo esc_html__("The REST API is crucial for many plugins as it allows them to interact with the WordPress database and perform various actions programmatically.", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_rest_api_param" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_rest_api_param" name="hmwp_disable_rest_api_param" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_rest_api_param') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_rest_api_param"><?php echo esc_html__('Disable "rest_route" param access', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_rest_api') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a></label>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__("Disable REST API access using the parameter 'rest_route'", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_xmlrpc" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_xmlrpc" name="hmwp_disable_xmlrpc" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_xmlrpc') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_xmlrpc"><?php echo esc_html__('Disable XML-RPC access', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#disable_xml_rpc_access') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo sprintf(esc_html__("Disable the access to /xmlrpc.php to prevent %sBrute force attacks via XML-RPC%s", 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/should-you-disable-xml-rpc-on-wordpress/" target="_blank">', '</a>'); ?></div>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__("Remove pingback link tag from the website header.", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_rsd" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_rsd" name="hmwp_hide_rsd" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_rsd') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_rsd"><?php echo esc_html__('Disable RSD Endpoint from XML-RPC', 'hide-my-wp'); ?>
                                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/customize-paths-in-hide-my-wp-ghost/#hide_rsd') ?>" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="text-black-50 ml-5"><?php echo esc_html__("Disable the RSD (Really Simple Discovery) support for XML-RPC & remove RSD tag from header", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div id="firewall" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Firewall & Headers', 'hide-my-wp'); ?>
                        <a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/kb/firewall-geo-security/') ?>" target="_blank" class="d-inline-block float-right mr-2" style="color: white"><i class="dashicons dashicons-editor-help"></i></a>
                    </h3>
                    <div class="card-body">
                        <div class="font-weight-bold p-2"><?php echo sprintf(esc_html__('The %s section has been relocated %s here %s', 'hide-my-wp'), esc_html__('Firewall & Headers', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_firewall').'">', '</a>'); ?></div>
                    </div>
                    <input type="hidden" name="hmwp_sqlinjection" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_sqlinjection') ?>"/>
                    <input type="hidden" name="hmwp_sqlinjection_level" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') ?>"/>
                    <input type="hidden" name="hmwp_hide_unsafe_headers" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_hide_unsafe_headers') ?>"/>
                    <input type="hidden" name="hmwp_sqlinjection_location" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_sqlinjection_location') ?>"/>
                    <input type="hidden" name="hmwp_hide_unsafe_headers" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_hide_unsafe_headers') ?>"/>
                    <input type="hidden" name="hmwp_detectors_block" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_detectors_block') ?>"/>
                    <input type="hidden" name="hmwp_security_header" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_security_header') ?>"/>

                </div>
                <div id="more" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Other Options', 'hide-my-wp'); ?></h3>
                    <div class="card-body">
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom category Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. cat, dir, list', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_category_base" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_category_base') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_category_base'] ?>"/>
                            </div>
                        </div>

                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-0 font-weight-bold">
                                <?php echo esc_html__('Custom tags Path', 'hide-my-wp'); ?>:
                                <div class="small text-black-50"><?php echo esc_html__('eg. keyword, topic', 'hide-my-wp'); ?></div>
                            </div>
                            <div class="col-sm-8 p-0 input-group">
                                <input type="text" class="form-control bg-input" maxlength="32" name="hmwp_tag_base" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_tag_base') ?>" placeholder="<?php echo HMWP_Classes_Tools::$default['hmwp_tag_base'] ?>"/>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (HMWP_Classes_Tools::getOption('test_frontend') || HMWP_Classes_Tools::getOption('logout') || HMWP_Classes_Tools::getOption('error') ) { ?>
                    <div class="col-sm-12 m-0 p-2">
                        <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__('Save', 'hide-my-wp'); ?></button>
                    </div>
                <?php } else { ?>
                    <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0 0 8px -3px #444;">
                        <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__('Save', 'hide-my-wp'); ?></button>
                    </div>
                <?php } ?>


            </form>
        </div>
        <div class="hmwp_col hmwp_col_side p-0 m-0 mr-2">
            <?php $view->show('blocks/ChangeCacheFiles'); ?>
            <?php $view->show('blocks/SecurityCheck'); ?>
            <?php $view->show('blocks/FrontendCheck'); ?>
            <?php
            if (!HMWP_Classes_Tools::getOption('api_token')){
                $view->show('blocks/Connect');
            }
            ?>
        </div>
    </div>
</div>

<div id="hmwp_safe_mode_modal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><?php echo esc_html__('Lite Mode', 'hide-my-wp') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">


                <h5 class="my-3">
                    <?php echo esc_html__('Lite Mode will set these predefined paths', 'hide-my-wp') ?>:
                </h5>

                <?php
                $default = HMWP_Classes_Tools::$default;
                $changed = @array_merge($default, HMWP_Classes_Tools::$lite);
                ?>

                <ul class="px-3">
                    <li><span><?php echo esc_html__('Login Path', 'hide-my-wp') ?>:</span> <strong>/<?php echo esc_html($default['hmwp_login_url'])?></strong> => <strong>/<?php echo esc_html($changed['hmwp_login_url']) ?></strong></li>
                    <li><span><?php echo esc_html__('Core Contents Path', 'hide-my-wp') ?>:</span> <strong>/<?php echo esc_html($default['hmwp_wp-content_url'])  ?></strong> => <strong>/<?php echo esc_html($changed['hmwp_wp-content_url']) ?></strong></li>
                    <li><span><?php echo esc_html__('Core Includes Path', 'hide-my-wp') ?>:</span> <strong>/<?php echo esc_html($default['hmwp_wp-includes_url'])?></strong> => <strong>/<?php echo esc_html($changed['hmwp_wp-includes_url']) ?></strong></li>
                    <li><span><?php echo esc_html__('Uploads Path', 'hide-my-wp') ?>:</span> <strong>/<?php echo esc_html($default['hmwp_wp-content_url'] .'/'. $default['hmwp_upload_url']) ?></strong> => <strong>/<?php echo esc_html($changed['hmwp_upload_url']) ?></strong></li>
                    <li><span><?php echo esc_html__('Author Path', 'hide-my-wp') ?>:</span> <strong>/<?php echo esc_html($default['hmwp_author_url']) ?></strong> => <strong>/<?php echo esc_html($changed['hmwp_author_url']) ?></strong></li>
                    <li><span><?php echo esc_html__('Plugins Path', 'hide-my-wp') ?>:</span> <strong>/<?php echo esc_html($default['hmwp_plugin_url']) ?></strong> => <strong>/<?php echo esc_html($changed['hmwp_plugin_url']) ?></strong></li>
                    <li><span><?php echo esc_html__('Themes Path', 'hide-my-wp') ?>:</span> <strong>/<?php echo esc_html($default['hmwp_wp-content_url'] .'/'. $default['hmwp_themes_url']) ?></strong> => <strong>/<?php echo esc_html($changed['hmwp_themes_url']) ?></strong></li>
                    <li><span><?php echo esc_html__('Comments Path', 'hide-my-wp') ?>:</span> <strong>/<?php echo esc_html($default['hmwp_wp-comments-post'])  ?></strong> => <strong>/<?php echo esc_html($changed['hmwp_wp-comments-post']) ?></strong></li>
                </ul>
                <div class="my-2 text-info">
                    <?php echo sprintf(esc_html__('Note! %sPaths are NOT physically change%s on your server.', 'hide-my-wp'), '<strong>', '</strong>') ?>
                </div>
                <div class="my-2">
                    <?php echo esc_html__('The Lite Mode will add the rewrites rules in the config file to hide the old paths from hackers.', 'hide-my-wp') ?>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row w-100">
                    <div class="col text-left">
                        <?php echo sprintf(esc_html__('Click %sContinue%s to set the predefined paths.', 'hide-my-wp'), '<strong>', '</strong>') ?><br />
                        <?php echo sprintf(esc_html__('After, click %sSave%s to apply the changes.', 'hide-my-wp'), '<strong>', '</strong>') ?>
                    </div>
                    <div class="col text-right">
                        <button type="button" class="btn btn-secondary safe_cancelaition" data-dismiss="modal"><?php echo esc_html__('Cancel', 'hide-my-wp') ?></button>
                        <button type="button" class="btn btn-success safe_confirmation" data-dismiss="modal"><?php echo esc_html__('Continue', 'hide-my-wp') ?> >></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


