<?php if(!isset($view)) return; ?>
<?php if (HMWP_Classes_Tools::getOption('test_frontend') && HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default' ) {
    add_action('home_url', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'home_url'), PHP_INT_MAX, 1);
    ?>
    <div class="col-sm-12 border-bottom border-light p-0 mx-0 my-3">

        <div class="col-sm-12 border-danger bg-light border py-3 mx-0 my-0">
            <h4><?php echo esc_html__('Next Steps', 'hide-my-wp'); ?></h4>

            <div class="text-center my-4">
                <div class="hmwp_confirm" style="display: inline-block; margin-right: 5px;">
                    <form class="hmwp_frontendcheck_form" method="POST">
                        <?php wp_nonce_field('hmwp_frontendcheck', 'hmwp_nonce') ?>
                        <input type="hidden" name="action" value="hmwp_frontendcheck"/>
                        <button type="button" class="btn rounded-0 btn-default btn-lg text-white px-4 frontend_test"><?php echo esc_html__('Frontend Test', 'hide-my-wp'); ?></button>
                    </form>
                </div>
                <div class="text-center" style="display: inline-block; margin-right: 5px;">
                    <button type="button" class="btn rounded-0 btn-default btn-lg text-white px-4 login_test hmwp_modal" data-remote="<?php echo esc_url(site_url() . '/' . HMWP_Classes_Tools::getOption('hmwp_login_url')) ?>" data-target="#frontend_test_modal" ><?php echo esc_html__('Login Test', 'hide-my-wp'); ?></button>
                </div>
            </div>
            <div id="hmwp_frontendcheck_content" class="my-3"></div>
            <div id="hmwp_solutions"  style="display: none">
                <div class="my-3 pt-3 border-top border-white text-center">
                    <?php if(HMWP_Classes_Tools::isApache() && !HMWP_Classes_Tools::isWpengine()) { ?>
                        <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-set-allowoverride-all/') ?>" target="_blank">Make sure to activate <strong>AllowOverride All</strong> for your website directory</a></div>
                    <?php }?>
                    <?php if(HMWP_Classes_Tools::isNginx()) { ?>
                        <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-setup-hide-my-wp-on-nginx-server/') ?>" target="_blank">Setup The Plugin On Nginx Server</a></div>
                        <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-configure-hide-my-wp-on-nginx-web-server-with-virtual-private-server/') ?>" target="_blank">Setup The Plugin On Nginx Server with Virtual Private Server</a></div>
                    <?php }?>
                    <?php if(HMWP_Classes_Tools::isWpengine()) { ?>
                        <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/hide-my-wp-pro-compatible-with-wp-engine/') ?>" target="_blank">Setup The Plugin On WP Engine</a></div>
                    <?php }?>
                    <?php if(HMWP_Classes_Tools::isGodaddy()) { ?>
                        <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/how-to-use-hide-my-wp-with-godaddy/') ?>" target="_blank">Setup The Plugin On Godaddy</a></div>
                    <?php }?>
                    <?php if(HMWP_Classes_Tools::isIIS()) { ?>
                        <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/setup-hide-my-wp-on-windows-iis-server/') ?>" target="_blank">Setup The Plugin On Windows IIS Server</a></div>
                    <?php }?>
                    <?php if(HMWP_Classes_Tools::isInmotion()) { ?>
                        <div class="mb-2"><a href="<?php echo esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website') . '/hide-my-wp-pro-compatible-with-inmotion-wordpress-hosting/') ?>" target="_blank">Setup The Plugin On Inmotion Server</a></div>
                    <?php }?>
                </div>
            </div>

            <ol>
                <li><?php echo sprintf(esc_html__("Run %s Frontend Test %s to check if the new paths are working.", 'hide-my-wp'), '<strong>', '</strong>'); ?></li>
                <li><?php echo sprintf(esc_html__("Run %s Login Test %s and log in inside the popup.", 'hide-my-wp'), '<strong>', '</strong>'); ?></li>
                <li><?php echo esc_html__("If you're able to log in, you've set the new paths correctly.", 'hide-my-wp'); ?></li>
                <li><?php echo esc_html__('Do not log out from this browser until you are confident that the Log in Page is working and you will be able to login again.', 'hide-my-wp'); ?></li>
                <li><?php echo sprintf(esc_html__("If you can't configure %s, switch to Deactivated Mode and %scontact us%s.", 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name'), '<a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/contact/" target="_blank" >', '</a>'); ?></li>
            </ol>

            <?php if (defined('HMWP_DEFAULT_LOGIN') && HMWP_DEFAULT_LOGIN ) {
                if(stripos(HMWP_DEFAULT_LOGIN,home_url()) !== false){
                    $custom_login = HMWP_DEFAULT_LOGIN;
                }else{
                    $custom_login = home_url(HMWP_DEFAULT_LOGIN);
                }
                ?>
                <div class="wp-admin_warning col-sm-12 my-4 text-danger p-0 text-center">
                    <div class="mb-3"><?php echo sprintf(esc_html__("Your login URL is: %s", 'hide-my-wp'), '<br /><a href="' . esc_url($custom_login) . '" target="_blank">' . esc_url($custom_login) . '</a>'); ?></div>
                </div>
            <?php }else{ ?>
                <div class="wp-admin_warning col-sm-12 my-4 text-danger p-0 text-center">
                    <div class="mb-3"><?php echo sprintf(esc_html__("Your login URL will be: %s In case you can't login, use the safe URL: %s", 'hide-my-wp'), '<br /><a href="' . esc_url(site_url() . '/' . HMWP_Classes_Tools::getOption('hmwp_login_url')) . '" target="_blank">' . esc_url(site_url() . '/' . HMWP_Classes_Tools::getOption('hmwp_login_url')) . '</a><br /><br />', "<br /><a href='".site_url() . "/wp-login.php?" . HMWP_Classes_Tools::getOption('hmwp_disable_name') . "=" . HMWP_Classes_Tools::getOption('hmwp_disable')."' target='_blank'>" . site_url() . "/wp-login.php?" . HMWP_Classes_Tools::getOption('hmwp_disable_name') . "=" . HMWP_Classes_Tools::getOption('hmwp_disable') . "</a>"); ?></div>
                </div>
            <?php }?>

            <div class="p-0 text-center">
                <div class="hmwp_confirm">
                    <form method="POST">
                        <?php wp_nonce_field('hmwp_confirm', 'hmwp_nonce'); ?>
                        <input type="hidden" name="action" value="hmwp_confirm"/>
                        <input type="submit" class="btn btn-success" value="<?php echo esc_html__("Yes, it's working", 'hide-my-wp') ?>"/>
                    </form>
                </div>
                <div class="hmwp_abort" style="display: inline-block; margin-left: 5px;">
                    <form method="POST">
                        <?php wp_nonce_field('hmwp_abort', 'hmwp_nonce'); ?>
                        <input type="hidden" name="action" value="hmwp_abort"/>
                        <input type="submit" class="btn btn-secondary" value="<?php echo esc_html__("No, abort", 'hide-my-wp') ?>"/>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal" id="frontend_test_modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel"><?php echo esc_html__('Frontend Login Test', 'hide-my-wp'); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <iframe class="modal-body" style="min-height: 500px;"></iframe>
                </div>
            </div>
        </div>

    </div>
<?php } ?>
