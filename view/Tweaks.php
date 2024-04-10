<?php if(!isset($view)) return; ?>
<?php
/**
 *
 *
 * @var $wp_roles WP_Roles
 */
global $wp_roles;

$allroles = array();
if (function_exists('wp_roles') ) {
    $allroles = wp_roles()->get_names();
}
?>
<noscript> <style>#hmwp_wrap .tab-panel:not(.tab-panel-first){display: block}</style> </noscript>
<div id="hmwp_wrap" class="d-flex flex-row p-0 my-3">
<?php echo $view->getAdminTabs(HMWP_Classes_Tools::getValue('page', 'hmwp_tweaks')); ?>
    <div class="hmwp_row d-flex flex-row p-0 m-0">
        <div class="hmwp_col flex-grow-1 px-3 py-3 mr-2 mb-3 bg-white">
            <form method="POST">
                <?php wp_nonce_field('hmwp_tweakssettings', 'hmwp_nonce') ?>
                <input type="hidden" name="action" value="hmwp_tweakssettings"/>

                <div id="redirects" class="card col-sm-12 p-0 m-0 tab-panel tab-panel-first">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Redirects', 'hide-my-wp'); ?></h3>
                    <div class="card-body">
                        <div class="col-sm-12 row border-bottom border-light py-3 mx-0 my-3">
                            <div class="col-sm-4 p-1">
                                <div class="font-weight-bold"><?php echo esc_html__('Redirect Hidden Paths', 'hide-my-wp'); ?>:</div>
                            </div>
                            <div class="col-sm-8 p-0 input-group mb-1">
                                <select name="hmwp_url_redirect" class="form-control bg-input">
                                    <option value="." <?php selected('.', HMWP_Classes_Tools::getOption('hmwp_url_redirect'), true) ?>><?php echo esc_html__("Front page", 'hide-my-wp') ?></option>
                                    <option value="404" <?php selected('404', HMWP_Classes_Tools::getOption('hmwp_url_redirect'), true) ?> ><?php echo esc_html__("404 page", 'hide-my-wp') ?></option>
                                    <option value="NFError" <?php selected('NFError', HMWP_Classes_Tools::getOption('hmwp_url_redirect'), true) ?> ><?php echo esc_html__("404 HTML Error", 'hide-my-wp') ?></option>
                                    <option value="NAError" <?php selected('NAError', HMWP_Classes_Tools::getOption('hmwp_url_redirect'), true) ?> ><?php echo esc_html__("403 HTML Error", 'hide-my-wp') ?></option>
                                    <?php
                                    $pages = get_pages(array('number' => 50));
                                    foreach ( $pages as $page ) {
                                        if ($page->post_title <> '' ) {
                                            ?><option value="<?php echo esc_attr($page->post_name) ?>" <?php echo selected($page->post_name, HMWP_Classes_Tools::getOption('hmwp_url_redirect'), true) ?> ><?php echo esc_html($page->post_title) ?></option><?php
                                        }
                                    } ?>
                                </select>
                                <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#redirect_paths" target="_blank" class="position-absolute float-right" style="right: 27px;top: 25%;"><i class="dashicons dashicons-editor-help"></i></a>
                            </div>
                            <div class="p-1">
                                <div class="text-black-50"><?php echo esc_html__('Redirect the protected paths /wp-admin, /wp-login to a Page or trigger an HTML Error.', 'hide-my-wp'); ?></div>
                                <div class="text-black-50"><?php echo esc_html__('You can create a new page and come back to choose to redirect to that page.', 'hide-my-wp'); ?></div>
                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_do_redirects" value="0"/>
                                    <input type="checkbox" id="hmwp_do_redirects" name="hmwp_do_redirects" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_do_redirects') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_do_redirects"><?php echo esc_html__('Do Login & Logout Redirects', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#redirect_on_login" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Add redirects for the logged users based on user roles", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 py-3 m-0 hmwp_do_redirects" >
                            <?php $urlRedirects = HMWP_Classes_Tools::getOption('hmwp_url_redirects');  ?>
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item m-0">
                                    <a class="nav-link active" data-toggle="tab" href="#default" role="tab" aria-controls="default" aria-selected="true"><?php echo esc_html__("Default", 'hide-my-wp') ?></a>
                                </li>
                                <?php if (!empty($allroles) ) { ?>
                                    <li class="nav-item dropdown m-0">
                                        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><?php echo esc_html__("User Role", 'hide-my-wp') ?></a>
                                        <div class="dropdown-menu" style="height: auto; max-height: 200px; overflow-x: hidden;">
                                            <?php foreach ( $allroles as $role => $name ) { ?>
                                                <a class="dropdown-item" data-toggle="tab" href="#nav-<?php echo esc_attr($role) ?>" role="tab" aria-controls="nav-<?php echo esc_attr($role) ?>" aria-selected="false"><?php echo esc_attr($name) ?></a>
                                            <?php } ?>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                            <div class="tab-content border-right border-left border-bottom p-0 m-0">
                                <div class="tab-pane show active" id="default" role="tabpanel" aria-labelledby="nav-home-tab">

                                    <div class="col-sm-12 row border-bottom border-light py-3 m-0">
                                        <div class="col-sm-4 p-0 py-2 font-weight-bold">
                                            <?php echo esc_html__('Login Redirect URL', 'hide-my-wp'); ?>:
                                            <div class="small text-black-50"><?php echo esc_html__("eg.", 'hide-my-wp') . ' ' . admin_url('', 'relative'); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group ">
                                            <input type="text" class="form-control bg-input mt-2" name="hmwp_url_redirects[default][login]" value="<?php echo(isset($urlRedirects['default']['login']) ? esc_url($urlRedirects['default']['login']) : '') ?>" />
                                            <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#redirect_on_login" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 row border-bottom border-light py-3 mx-0">
                                        <div class="col-sm-4 p-0 py-2 font-weight-bold">
                                            <?php echo esc_html__('Logout Redirect URL', 'hide-my-wp'); ?>:
                                            <div class="small text-black-50"><?php echo esc_html__("eg. /logout or ", 'hide-my-wp') . ' ' . home_url('', 'relative'); ?></div>
                                        </div>
                                        <div class="col-sm-8 p-0 input-group">
                                            <input type="text" class="form-control bg-input mt-2" name="hmwp_url_redirects[default][logout]" value="<?php echo(isset($urlRedirects['default']['logout']) ? esc_url($urlRedirects['default']['logout']) : '') ?>" />
                                            <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#redirect_on_login" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                        </div>
                                    </div>

                                    <div class="p-3">
                                        <div class="p-2 text-danger"><?php echo sprintf(esc_html__("Make sure the redirect URLs exist on your website. %sThe User Role redirect URL has higher priority than the Default redirect URL.", 'hide-my-wp'), '<br />'); ?></div>
                                    </div>
                                </div>

                                <?php if (!empty($allroles) ) {
                                    foreach ( $allroles as $role => $name ) { ?>
                                        <div class="tab-pane" id="nav-<?php echo esc_attr($role) ?>" role="tabpanel" aria-labelledby="nav-profile-tab">
                                            <h5 class="card-title pt-3 pb-1 mx-3 text-black-50 border-bottom border-light"><?php echo esc_html(ucwords(str_replace('_', ' ', $role))) . ' ' . esc_html__("redirects", 'hide-my-wp'); ?>:</h5>
                                            <div class="col-sm-12 row border-bottom border-light py-3 m-0">
                                                <div class="col-sm-4 p-0 py-2 font-weight-bold">
                                                    <?php echo esc_html__('Login Redirect URL', 'hide-my-wp'); ?>:
                                                    <div class="small text-black-50"><?php echo esc_html__("eg.", 'hide-my-wp') . ' ' . admin_url('', 'relative'); ?></div>
                                                </div>
                                                <div class="col-sm-8 p-0 input-group">
                                                    <input type="text" class="form-control bg-input mt-2" name="hmwp_url_redirects[<?php echo esc_attr($role) ?>][login]" value="<?php echo(isset($urlRedirects[$role]['login']) ? esc_url($urlRedirects[$role]['login']) : '') ?>"/>
                                                    <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#redirect_on_login" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                                </div>
                                            </div>

                                            <div class="col-sm-12 row border-bottom border-light py-3 m-0">
                                                <div class="col-sm-4 p-0 py-2 font-weight-bold">
                                                    <?php echo esc_html__('Logout Redirect URL', 'hide-my-wp'); ?>:
                                                    <div class="small text-black-50"><?php echo esc_html__("eg. /logout or ", 'hide-my-wp') . ' ' . home_url('', 'relative'); ?></div>
                                                </div>
                                                <div class="col-sm-8 p-0 input-group">
                                                    <input type="text" class="form-control bg-input mt-2" name="hmwp_url_redirects[<?php echo esc_attr($role) ?>][logout]" value="<?php echo(isset($urlRedirects[$role]['logout']) ? esc_url($urlRedirects[$role]['logout']) : '') ?>"/>
                                                    <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#redirect_on_login" target="_blank" class="position-absolute float-right" style="right: 7px;top: 20%;"><i class="dashicons dashicons-editor-help"></i></a>
                                                </div>
                                            </div>

                                            <div class="p-3">
                                                <div class="p-2 text-danger"><?php echo sprintf(esc_html__("Make that the redirect URLs exist on your website. %sThe User Role redirect URL has higher priority than the Default redirect URL.", 'hide-my-wp'), '<br />'); ?></div>
                                            </div>
                                        </div>
                                    <?php }
                                } ?>


                            </div>
                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_logged_users_redirect" value="0"/>
                                    <input type="checkbox" id="hmwp_logged_users_redirect" name="hmwp_logged_users_redirect" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_logged_users_redirect') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_logged_users_redirect"><?php echo esc_html__('Redirect Logged Users To Dashboard', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#auto_redirect_on_login" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Automatically redirect the logged in users to the admin dashboard", 'hide-my-wp'); ?>.</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div id="sitemap" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Feed & Sitemap', 'hide-my-wp'); ?></h3>
                    <?php if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' ) { ?>
                        <div class="card-body">
                            <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
	                            <?php echo sprintf(esc_html__('First, you need to activate the %sLite Mode%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks').'">', '</a>') ?>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="card-body">
                            <div class="col-sm-12 row mb-1 ml-1 p-2">

                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_feed" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_feed" name="hmwp_hide_feed" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_feed') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_hide_feed"><?php echo esc_html__('Hide Feed & Sitemap Link Tags', 'hide-my-wp'); ?>
                                                <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#hide_sitemap_xml" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="offset-1 text-black-50"><?php echo esc_html__('Hide the /feed and /sitemap.xml link Tags', 'hide-my-wp'); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_in_feed" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_in_feed" name="hmwp_hide_in_feed" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_in_feed') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_hide_in_feed"><?php echo esc_html__('Change Paths in RSS feed', 'hide-my-wp'); ?>
                                                <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#fix_rss_feed" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="offset-1 text-black-50"><?php echo sprintf(esc_html__('Check the %s RSS feed %s and make sure the image paths are changed.', 'hide-my-wp'), '<a href="'.site_url().'/rss" target="_blank"><strong>', '</strong></a>'); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_in_sitemap" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_in_sitemap" name="hmwp_hide_in_sitemap" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_in_sitemap') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_hide_in_sitemap"><?php echo esc_html__('Change Paths in Sitemaps XML', 'hide-my-wp'); ?>
                                                <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#fix_sitemap_xml" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="offset-1 text-black-50"><?php echo sprintf(esc_html__('Check the %s Sitemap XML %s and make sure the image paths are changed.', 'hide-my-wp'), '<a href="'.site_url().'/sitemap.xml" target="_blank"><strong>', '</strong></a>'); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hide_in_sitemap">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_hide_author_in_sitemap" value="0"/>
                                            <input type="checkbox" id="hmwp_hide_author_in_sitemap" name="hmwp_hide_author_in_sitemap" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_author_in_sitemap') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_hide_author_in_sitemap"><?php echo esc_html__('Remove Plugins Authors & Style in Sitemap XML', 'hide-my-wp'); ?>
                                                <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#fix_sitemap_xml" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="offset-1 text-black-50"><?php echo esc_html__("To improve your website's security, consider removing authors and styles that point to WordPress in your sitemap XML.", 'hide-my-wp'); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 row mb-1 ml-1 p-2">
                                    <div class="checker col-sm-12 row my-2 py-1">
                                        <div class="col-sm-12 p-0 switch switch-sm">
                                            <input type="hidden" name="hmwp_robots" value="0"/>
                                            <input type="checkbox" id="hmwp_robots" name="hmwp_robots" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_robots') ? 'checked="checked"' : '') ?> value="1"/>
                                            <label for="hmwp_robots"><?php echo esc_html__('Hide Paths in Robots.txt', 'hide-my-wp'); ?>
                                                <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#fix_robots_txt" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                            </label>
                                            <div class="offset-1 text-black-50"><?php echo sprintf(esc_html__('Hide WordPress common paths from %s Robots.txt %s file.', 'hide-my-wp'), '<a href="'.site_url().'/robots.txt" target="_blank"><strong>', '</strong></a>'); ?></div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php }?>
                </div>

                <div id="changes" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Change Options', 'hide-my-wp'); ?></h3>
                    <?php if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' ) { ?>
                        <div class="card-body">
                            <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
	                            <?php echo sprintf(esc_html__('First, you need to activate the %sLite Mode%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks').'">', '</a>') ?>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="card-body">

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_hide_loggedusers" value="0"/>
                                        <input type="checkbox" id="hmwp_hide_loggedusers" name="hmwp_hide_loggedusers" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_loggedusers') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_hide_loggedusers"><?php echo esc_html__('Change Paths for Logged Users', 'hide-my-wp'); ?>
                                            <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#change_paths_logged_users" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__("Change WordPress paths while you're logged in", 'hide-my-wp'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 p-2">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_fix_relative" value="0"/>
                                        <input type="checkbox" id="hmwp_fix_relative" name="hmwp_fix_relative" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_fix_relative') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_fix_relative"><?php echo esc_html__('Change Relative URLs to Absolute URLs', 'hide-my-wp'); ?>
                                            <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#fix_relative_urls" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        </label>
                                        <div class="offset-1 text-black-50"><?php echo sprintf(esc_html__('Convert links like /wp-content/* into  %s/wp-content/*.', 'hide-my-wp'), site_url()); ?></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php }?>
                </div>

                <div id="hide" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0"><?php echo esc_html__('Hide Options', 'hide-my-wp'); ?></h3>
                    <?php if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' ) { ?>
                        <div class="card-body">
                            <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
	                            <?php echo sprintf(esc_html__('First, you need to activate the %sLite Mode%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks').'">', '</a>') ?>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="card-body">
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_admin_toolbar" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_admin_toolbar" name="hmwp_hide_admin_toolbar" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_admin_toolbar') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_admin_toolbar"><?php echo esc_html__('Hide Admin Toolbar', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#admin_toolbar" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__('Hide the admin toolbar for logged users while in frontend.', 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                            <div class="col-sm-12 row border-bottom border-light py-3 mx-1 my-3 hmwp_hide_admin_toolbar border-bottom">
                                <div class="col-sm-4 p-1">
                                    <div class="font-weight-bold"><?php echo esc_html__('Select User Roles', 'hide-my-wp'); ?>:</div>
                                    <div class="small text-black-50"><?php echo esc_html__("User roles for who to hide the admin toolbar", 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-8 p-0 input-group">
                                    <select multiple name="hmwp_hide_admin_toolbar_roles[]" class="selectpicker form-control mb-1">
                                        <?php

                                        $selected_roles = (array)HMWP_Classes_Tools::getOption('hmwp_hide_admin_toolbar_roles');

                                        foreach ( $allroles as $role => $name) {
                                            echo '<option value="' . $role . '" ' . (in_array($role, $selected_roles) ? 'selected="selected"' : '') . '>' . esc_attr($name) . '</option>';
                                        } ?>

                                    </select>
                                </div>

                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_version" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_version" name="hmwp_hide_version" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_version') ? 'checked="checked"' : '') ?>value="1"/>
                                    <label for="hmwp_hide_version"><?php echo esc_html__('Hide Version from Images, CSS and JS in WordPress', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#hide_wordpress_version" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Hide all versions from the end of any Image, CSS and JavaScript files", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2 hmwp_hide_version">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_version_random" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_version_random" name="hmwp_hide_version_random" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_version_random') ? 'checked="checked"' : '') ?>value="<?php echo mt_rand(11111,99999) ?>"/>
                                    <label for="hmwp_hide_version_random"><?php echo esc_html__('Random Static Number', 'hide-my-wp'); ?></label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Add a random static number to prevent frontend caching while the user is logged in.", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_styleids" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_styleids" name="hmwp_hide_styleids" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_styleids') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_styleids"><?php echo esc_html__('Hide IDs from META Tags', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#hide_ids_tags" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                        <span class="text-black-50 small">(<?php echo esc_html__("not recommended", 'hide-my-wp'); ?>)</span> </label>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Hide the IDs from all &lt;links&gt;, &lt;style&gt;, &lt;scripts&gt; META Tags", 'hide-my-wp'); ?></div>
                                    <div class="offset-1 text-danger my-2"><?php echo esc_html__("Hiding the ID from meta tags in WordPress can potentially impact the caching process of plugins that rely on identifying the meta tags.", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_prefetch" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_prefetch" name="hmwp_hide_prefetch" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_prefetch') ? 'checked="checked"' : '') ?>value="1"/>
                                    <label for="hmwp_hide_prefetch"><?php echo esc_html__('Hide WordPress DNS Prefetch META Tags', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#hide_dns_prefetch" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Hide the DNS Prefetch that points to WordPress", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_generator" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_generator" name="hmwp_hide_generator" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_generator') ? 'checked="checked"' : '') ?>value="1"/>
                                    <label for="hmwp_hide_generator"><?php echo esc_html__('Hide WordPress Generator META Tags', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#hide_generator_meta" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Hide the WordPress Generator META tags", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_hide_comments" value="0"/>
                                    <input type="checkbox" id="hmwp_hide_comments" name="hmwp_hide_comments" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_hide_comments') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_hide_comments"><?php echo esc_html__('Hide HTML Comments', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#hide_comments" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Hide the HTML Comments left by the themes and plugins", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_emojicons" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_emojicons" name="hmwp_disable_emojicons" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_emojicons') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_emojicons"><?php echo esc_html__('Hide Emojicons', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#hide_emojicons" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Don't load Emoji Icons if you don't use them", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_embeds" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_embeds" name="hmwp_disable_embeds" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_embeds') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_embeds"><?php echo esc_html__('Hide Embed scripts', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#disable_embed_scripts" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Don't load oEmbed service if you don't use oEmbed videos", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 row mb-1 ml-1 p-2">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_manifest" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_manifest" name="hmwp_disable_manifest" class="switch"<?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_manifest') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_manifest"><?php echo esc_html__('Hide WLW Manifest scripts', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#disable_wlw_scripts" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Don't load WLW if you didn't configure Windows Live Writer for your site", 'hide-my-wp'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php }?>
                </div>

                <div id="disable" class="card col-sm-12 p-0 m-0 tab-panel">
                    <h3 class="card-title hmwp_header p-2 m-0">
                        <?php echo esc_html__('Disable Options', 'hide-my-wp'); ?>
                        <div class="col-sm-12 border-0 p-0 mx-0 text-black-50 text-left small">
                            <?php echo esc_html__('This feature requires jQuery library on frontend.', 'hide-my-wp') ?>
                        </div>
                    </h3>
                    <?php if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' ) { ?>
                        <div class="card-body">
                            <div class="col-sm-12 border-0 py-3 mx-0 my-3 text-black-50 text-center">
	                            <?php echo sprintf(esc_html__('First, you need to activate the %sLite Mode%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks').'">', '</a>') ?>
                            </div>
                        </div>
                    <?php } else { ?>
                        <div class="card-body">

                        <div class="col-sm-12 row mb-1 ml-1 border-bottom">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_click" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_click" name="hmwp_disable_click" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_click') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_click"><?php echo esc_html__('Disable Right-Click', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#disable_mouse" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Disable the right-click functionality on your website", 'hide-my-wp'); ?></div>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_click" >
                                <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                    <?php echo esc_html__('Disable Click Message', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__("Leave it blank if you don't want to display any message", 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-7 p-0 input-group">
                                    <input type="text" class="form-control bg-input mt-2" name="hmwp_disable_click_message" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_disable_click_message') ?>" />
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 hmwp_disable_click">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_click_loggedusers" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_click_loggedusers" name="hmwp_disable_click_loggedusers" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_click_loggedusers') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_disable_click_loggedusers"><?php echo esc_html__('Disable Right-Click for Logged Users', 'hide-my-wp'); ?></label>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_click_loggedusers hmwp_disable_click">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__('Select User Roles', 'hide-my-wp'); ?>:</div>
                                        <div class="small text-black-50"><?php echo esc_html__("User roles for who to disable the Right-Click", 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select multiple name="hmwp_disable_click_roles[]" class="selectpicker form-control mb-1">
				                            <?php

				                            $selected_roles = (array)HMWP_Classes_Tools::getOption('hmwp_disable_click_roles');

                                            foreach ( $allroles as $role => $name) {
                                                echo '<option value="' . $role . '" ' . (in_array($role, $selected_roles) ? 'selected="selected"' : '') . '>' . esc_attr($name) . '</option>';
                                            } ?>

                                        </select>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 border-bottom">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_inspect" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_inspect" name="hmwp_disable_inspect" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_inspect') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_inspect"><?php echo esc_html__('Disable Inspect Element', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#disable_mouse" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Disable the inspect element view on your website", 'hide-my-wp'); ?></div>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_inspect" >
                                <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                    <?php echo esc_html__('Disable Inspect Element Message', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__("Leave it blank if you don't want to display any message", 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-7 p-0 input-group">
                                    <input type="text" class="form-control bg-input mt-2" name="hmwp_disable_inspect_message" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_disable_inspect_message') ?>" />
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 hmwp_disable_inspect">

                                <div class="checker col-sm-12 row my-2 py-1 hmwp_disable_inspect">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_inspect_blank" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_inspect_blank" name="hmwp_disable_inspect_blank" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_inspect_blank') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_disable_inspect_blank"><?php echo esc_html__('Blank Screen On Debugging', 'hide-my-wp'); ?> <em>(<?php echo esc_html__('not recommended', 'hide-my-wp'); ?>)</em></label>
                                        <div class="offset-1 text-black-50"><?php echo esc_html__("Show blank screen when Inspect Element is active on browser.", 'hide-my-wp'); ?></div>
                                        <div class="offset-1 text-danger"><?php echo esc_html__("This may not work with all new mobile devices.", 'hide-my-wp'); ?></div>
                                    </div>
                                </div>

                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_inspect_loggedusers" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_inspect_loggedusers" name="hmwp_disable_inspect_loggedusers" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_inspect_loggedusers') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_disable_inspect_loggedusers"><?php echo esc_html__('Disable Inspect Element for Logged Users', 'hide-my-wp'); ?></label>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_inspect_loggedusers">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__('Select User Roles', 'hide-my-wp'); ?>:</div>
                                        <div class="small text-black-50"><?php echo esc_html__("User roles for who to disable the inspect element", 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select multiple name="hmwp_disable_inspect_roles[]" class="selectpicker form-control mb-1">
				                            <?php

				                            $selected_roles = (array)HMWP_Classes_Tools::getOption('hmwp_disable_inspect_roles');

                                            foreach ( $allroles as $role => $name) {
                                                echo '<option value="' . $role . '" ' . (in_array($role, $selected_roles) ? 'selected="selected"' : '') . '>' . esc_attr($name) . '</option>';
                                            } ?>

                                        </select>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 border-bottom">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_source" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_source" name="hmwp_disable_source" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_source') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_source"><?php echo esc_html__('Disable View Source', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#disable_mouse" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Disable the source-code view on your website", 'hide-my-wp'); ?></div>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_source" >
                                <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                    <?php echo esc_html__('Disable View Source Message', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__("Leave it blank if you don't want to display any message", 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-7 p-0 input-group">
                                    <input type="text" class="form-control bg-input mt-2" name="hmwp_disable_source_message" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_disable_source_message') ?>" />
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 hmwp_disable_source">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_source_loggedusers" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_source_loggedusers" name="hmwp_disable_source_loggedusers" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_source_loggedusers') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_disable_source_loggedusers"><?php echo esc_html__('Disable View Source for Logged Users', 'hide-my-wp'); ?></label>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_source_loggedusers">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__('Select User Roles', 'hide-my-wp'); ?>:</div>
                                        <div class="small text-black-50"><?php echo esc_html__("User roles for who to disable the view source", 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select multiple name="hmwp_disable_source_roles[]" class="selectpicker form-control mb-1">
				                            <?php

				                            $selected_roles = (array)HMWP_Classes_Tools::getOption('hmwp_disable_source_roles');

                                            foreach ( $allroles as $role => $name) {
                                                echo '<option value="' . $role . '" ' . (in_array($role, $selected_roles) ? 'selected="selected"' : '') . '>' . esc_attr($name) . '</option>';
                                            } ?>

                                        </select>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 border-bottom">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_copy_paste" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_copy_paste" name="hmwp_disable_copy_paste" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_copy_paste"><?php echo esc_html__('Disable Copy/Paste', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#disable_mouse" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Disable copy & paste functions on your website", 'hide-my-wp'); ?></div>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_copy_paste" >
                                <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                    <?php echo esc_html__('Disable Copy/Paste Message', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__("Leave it blank if you don't want to display any message", 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-7 p-0 input-group">
                                    <input type="text" class="form-control bg-input mt-2" name="hmwp_disable_copy_paste_message" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste_message') ?>" />
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 hmwp_disable_copy_paste">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_copy_paste_loggedusers" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_copy_paste_loggedusers" name="hmwp_disable_copy_paste_loggedusers" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste_loggedusers') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_disable_copy_paste_loggedusers"><?php echo esc_html__('Disable Copy/Paste for Logged Users', 'hide-my-wp'); ?></label>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_copy_paste_loggedusers">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__('Select User Roles', 'hide-my-wp'); ?>:</div>
                                        <div class="small text-black-50"><?php echo esc_html__("User roles for who to disable the copy/paste", 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select multiple name="hmwp_disable_copy_paste_roles[]" class="selectpicker form-control mb-1">
				                            <?php

				                            $selected_roles = (array)HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste_roles');

                                            foreach ( $allroles as $role => $name) {
                                                echo '<option value="' . $role . '" ' . (in_array($role, $selected_roles) ? 'selected="selected"' : '') . '>' . esc_attr($name) . '</option>';
                                            } ?>

                                        </select>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="col-sm-12 row mb-1 ml-1 border-bottom">
                            <div class="checker col-sm-12 row my-2 py-1">
                                <div class="col-sm-12 p-0 switch switch-sm">
                                    <input type="hidden" name="hmwp_disable_drag_drop" value="0"/>
                                    <input type="checkbox" id="hmwp_disable_drag_drop" name="hmwp_disable_drag_drop" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop') ? 'checked="checked"' : '') ?> value="1"/>
                                    <label for="hmwp_disable_drag_drop"><?php echo esc_html__('Disable Drag/Drop Images', 'hide-my-wp'); ?>
                                        <a href="<?php echo HMWP_Classes_Tools::getOption('hmwp_plugin_website') ?>/kb/activate-security-tweaks/#disable_mouse" target="_blank" class="d-inline ml-1"><i class="dashicons dashicons-editor-help d-inline"></i></a>
                                    </label>
                                    <div class="offset-1 text-black-50"><?php echo esc_html__("Disable image drag & drop on your website", 'hide-my-wp'); ?></div>
                                </div>
                            </div>

                            <div class="col-sm-12 row py-3 mx-0 my-3 hmwp_disable_drag_drop" >
                                <div class="col-sm-5 p-0 pr-3 font-weight-bold">
                                    <?php echo esc_html__('Disable Drag/Drop Message', 'hide-my-wp'); ?>:
                                    <div class="small text-black-50"><?php echo esc_html__("Leave it blank if you don't want to display any message", 'hide-my-wp'); ?></div>
                                </div>
                                <div class="col-sm-7 p-0 input-group">
                                    <input type="text" class="form-control bg-input mt-2" name="hmwp_disable_drag_drop_message" value="<?php echo HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop_message') ?>" />
                                </div>
                            </div>

                            <div class="col-sm-12 row mb-1 ml-1 hmwp_disable_drag_drop">
                                <div class="checker col-sm-12 row my-2 py-1">
                                    <div class="col-sm-12 p-0 switch switch-sm">
                                        <input type="hidden" name="hmwp_disable_drag_drop_loggedusers" value="0"/>
                                        <input type="checkbox" id="hmwp_disable_drag_drop_loggedusers" name="hmwp_disable_drag_drop_loggedusers" class="switch" <?php echo(HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop_loggedusers') ? 'checked="checked"' : '') ?> value="1"/>
                                        <label for="hmwp_disable_drag_drop_loggedusers"><?php echo esc_html__('Disable Drag/Drop for Logged Users', 'hide-my-wp'); ?></label>
                                    </div>
                                </div>

                                <div class="col-sm-12 row py-3 mx-1 my-3 hmwp_disable_drag_drop_loggedusers">
                                    <div class="col-sm-4 p-1">
                                        <div class="font-weight-bold"><?php echo esc_html__('Select User Roles', 'hide-my-wp'); ?>:</div>
                                        <div class="small text-black-50"><?php echo esc_html__("User roles for who to disable the drag/drop", 'hide-my-wp'); ?></div>
                                    </div>
                                    <div class="col-sm-8 p-0 input-group">
                                        <select multiple name="hmwp_disable_drag_drop_roles[]" class="selectpicker form-control mb-1">
				                            <?php

				                            $selected_roles = (array)HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop_roles');

                                            foreach ( $allroles as $role => $name) {
                                                echo '<option value="' . $role . '" ' . (in_array($role, $selected_roles) ? 'selected="selected"' : '') . '>' . esc_attr($name) . '</option>';
                                            } ?>

                                        </select>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>
                    <?php }?>
                </div>

                <div class="col-sm-12 m-0 p-2 bg-light text-center" style="position: fixed; bottom: 0; right: 0; z-index: 100; box-shadow: 0px 0px 8px -3px #444;">
                    <button type="submit" class="btn rounded-0 btn-success px-5 mr-5 save"><?php echo esc_html__('Save', 'hide-my-wp'); ?></button>
                </div>
            </form>
        </div>
        <div class="hmwp_col hmwp_col_side p-0 m-0 mr-2">
            <?php $view->show('blocks/ChangeCacheFiles'); ?>
            <?php $view->show('blocks/SecurityCheck'); ?>
        </div>

    </div>

</div>
