<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Controllers_Settings extends HMW_Classes_FrontController {

    public $tabs;
    public $logout = false;
    public $show_token = false;
    public $plugins;

    public function __construct() {
        parent::__construct();

        //Show the errors when not on Hide My WP Settings
        if (HMW_Classes_Tools::getOption('logout') && !HMW_Classes_Tools::getOption('error')) {
            if (HMW_Classes_Tools::getValue('action') == '' && HMW_Classes_Tools::getValue('page') <> 'hmw_settings') {
                add_action('admin_notices', array($this, 'showReconnectError'));
            }
        }

        //If save settings is required, show the alert
        if (HMW_Classes_Tools::getOption('changes')) {
            if (HMW_Classes_Tools::getValue('page') <> 'hmw_settings') {
                add_action('admin_notices', array($this, 'showSaveRequires'));
            }
        }

    }

    public function init() {
        //We need that function so make sure is loaded
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }

        //Add the Plugin Paths in variable
        $this->plugins = $this->model->getPlugins();

        if (HMW_Classes_Tools::getOption('changes')) {
            add_action('hmw_form_notices', array($this, 'showSaveButton'));
        }

        //Settings Alerts based on Logout and Error statements
        if (HMW_Classes_Tools::getOption('logout') && !HMW_Classes_Tools::getOption('error')) {
            $logoutForm = '
                        <style>#hmw_settings { display: none; }</style>
                        <form method="POST">
                            ' . wp_nonce_field('hmw_logout', 'hmw_nonce', true, false) . '
                            <input type="hidden" name="action" value="hmw_logout" />
                            <input type="submit" class="hmw_btn hmw_btn-success" value="' . __("Yes, I'm ready to re-login", _HMW_PLUGIN_NAME_) . '" />
                        </form>
                        ';
            $abortForm = '
                        <form method="POST">
                            ' . wp_nonce_field('hmw_abort', 'hmw_nonce', true, false) . '
                            <input type="hidden" name="action" value="hmw_abort" />
                            <input type="submit" class="hmw_btn hmw_btn-warning" value="' . __("No, abort", _HMW_PLUGIN_NAME_) . '" />
                        </form>
                        ';
            HMW_Classes_Error::setError(sprintf(__("Your login URL will be: %s In case you can't re-login, use the safe URL: %s", _HMW_PLUGIN_NAME_), '<strong>' . home_url() . '/' . HMW_Classes_Tools::getOption('hmw_login_url') . '</strong><br />', "<strong>" . site_url() . "/wp-login.php?" . HMW_Classes_Tools::getOption('hmw_disable_name') . "=" . HMW_Classes_Tools::getOption('hmw_disable') . "</strong>"));
            HMW_Classes_Error::setError(sprintf(__('To activate the new Hide My Wp settings you need to confirm and re-login! %s', _HMW_PLUGIN_NAME_), '<div class="hmw_logout">' . $logoutForm . '</div><div class="hmw_abort" style="display: inline-block; margin-left: 5px;">' . $abortForm . '</div>'));
        } elseif (HMW_Classes_Tools::getOption('error')) {
            $abortForm = '
                        <form method="POST">
                            ' . wp_nonce_field('hmw_abort', 'hmw_nonce', true, false) . '
                            <input type="hidden" name="action" value="hmw_abort" />
                            <input type="submit" class="hmw_btn hmw_btn-warning" value="' . __("Cancel the changes", _HMW_PLUGIN_NAME_) . '" />
                        </form>
                        ';
            HMW_Classes_Error::setError(__('Action Required. Proceed with the instructions or cancel the changes ', _HMW_PLUGIN_NAME_) . '<div class="hmw_abort" style="display: inline-block;">' . $abortForm . '</div>');

        } elseif (get_transient('hmw_restore') == 1) {
            $restoreForm = '
                        <form method="POST">
                            ' . wp_nonce_field('hmw_abort', 'hmw_nonce', true, false) . '
                            <input type="hidden" name="action" value="hmw_abort" />
                            <input type="submit" class="hmw_btn hmw_btn-warning" value="' . __("Restore Settings", _HMW_PLUGIN_NAME_) . '" />
                        </form>
                        ';
            HMW_Classes_Error::setError(__('You want to restore the last saved settings? ', _HMW_PLUGIN_NAME_) . '<div class="hmw_abort" style="display: inline-block;">' . $restoreForm . '</div>');
            // Delete the redirect transient
            delete_transient('hmw_restore');

        }

        //Check compatibilities with other plugins
        HMW_Classes_ObjController::getClass('HMW_Models_Compatibility')->getAlerts();

        //Load the css for Settings

        HMW_Classes_ObjController::getClass('HMW_Classes_DisplayController')->loadMedia('bootstrap.min');
        HMW_Classes_ObjController::getClass('HMW_Classes_DisplayController')->loadMedia('font-awesome.min');
        HMW_Classes_ObjController::getClass('HMW_Classes_DisplayController')->loadMedia('switchery.min');
        HMW_Classes_ObjController::getClass('HMW_Classes_DisplayController')->loadMedia('settings');

       	    //Show Hide My WP Offer
	    if (HMW_Classes_Tools::getOption('hmw_mode') == 'lite') {
		    if(gmdate('Y-m-d') >= '2020-01-01' && gmdate('Y-m-d') <= '2020-01-10') {
			    HMW_Classes_Error::setError(__('<strong style="color: red">Happy 2020!</strong> We wish you a beautiful, magical new year! Enjoy the new Hide My WP Ghost features.', _HMW_PLUGIN_NAME_));
		    }elseif(gmdate('Y-m-d') >= '2020-01-15' && gmdate('Y-m-d') <= '2020-01-31') {
			    HMW_Classes_Error::setError(sprintf(__('%sLimited Time Offer%s: Get %s65%% OFF%s today on Hide My WP Ghost 5 Websites License. %sHurry Up!%s', _HMW_PLUGIN_NAME_), '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold">', '</a>'));
		    }elseif(gmdate('Y-m-d') >= '2020-02-15' && gmdate('Y-m-d') <= '2020-02-28') {
			    HMW_Classes_Error::setError(sprintf(__('%sLimited Time Offer%s: Get %s65%% OFF%s today on Hide My WP Ghost 5 Websites License. %sHurry Up!%s', _HMW_PLUGIN_NAME_), '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold">', '</a>'));
		    }elseif(gmdate('Y-m-d') >= '2020-03-15' && gmdate('Y-m-d') <= '2020-03-28') {
			    HMW_Classes_Error::setError(sprintf(__('%sLimited Time Offer%s: Get %s65%% OFF%s today on Hide My WP Ghost 5 Websites License. %sHurry Up!%s', _HMW_PLUGIN_NAME_), '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold"><strong style="color: red">', '</strong></a>', '<a href="https://wpplugins.tips/buy/5_websites_special" target="_blank" style="font-weight: bold">', '</a>'));
		    }
	    }

        //Show errors on top
        HMW_Classes_ObjController::getClass('HMW_Classes_Error')->hookNotices();


        //Show connect for activation
        if (!HMW_Classes_Tools::getOption('hmw_token')) {
            echo $this->getView('Connect');
            return;
        }

        //Add the Menu Tabs in variable
        $this->tabs = $this->model->getTabs();

        //Check if it's a subpage
        $page = HMW_Classes_Tools::getValue('page', 'hmw_settings');
        if (strpos($page, '-') !== false) {
            $_GET['tab'] = substr($page, (strpos($page, '-') + 1));
        }

        //Show the Tab Content
        foreach ($this->tabs as $slug => $value) {
            if (HMW_Classes_Tools::getValue('tab', 'hmw_permalinks') == $slug) {
                if (isset($value['class']) && $value['class'] <> '') {
                    echo HMW_Classes_ObjController::getClass($value['class'])->init()->getView();
                } else {
                    echo $this->getView(ucfirst(str_replace('hmw_', '', $slug)));
                }
            }
        }


    }

    /**
     * Show this message to notify the user when to update th esettings
     */
    public function showSaveRequires() {
        global $pagenow;
        if ($pagenow == 'plugins.php') {
            ?>
            <div class="hmw_notice error notice" style="margin-left: 0;">
                <div style="display: inline-block;">
                    <p>
                        <?php echo sprintf(__("New Plugin/Theme detected! You need to save the Hide My WP Setting again to include them all! %sClick here%s", _HMW_PLUGIN_NAME_), '<a href="' . HMW_Classes_Tools::getSettingsUrl() . '" >', '</a>'); ?>
                    </p>
                </div>
            </div>
            <?php
        }
    }

    public function showSaveButton() {
        ?>
        <div class="col-sm-12 mx-0 hmw_notice error notice">
            <div style="display: inline-block;">
                <p>
                    <?php echo sprintf(__("New Plugin/Theme detected! You need to save the Hide My WP Setting again to include them all! %sSave Settings%s", _HMW_PLUGIN_NAME_), '<button type="submit" class="btn btn-success btn-sm mx-2">', '</button>'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Show the reconnect alert on all pages
     */
    public function showReconnectError() {
        HMW_Classes_ObjController::getClass('HMW_Classes_DisplayController')->loadMedia('alert');
        ?>
        <div class="hmw_notice error notice" style="margin-left: 0; padding: 5px;">
            <div style="display: inline-block;">
                <p>
                    <?php echo sprintf(__("To activate the new %sHide My WP %s %s settings you need to confirm and re-login!", _HMW_PLUGIN_NAME_), '<strong>', _HMW_VER_NAME_, '</strong>'); ?>
                </p>
            </div>

            <div class="hmw_logout">
                <form method="POST" action="<?php echo HMW_Classes_Tools::getSettingsUrl() ?>">
                    <?php wp_nonce_field('hmw_logout', 'hmw_nonce') ?>
                    <input type="hidden" name="action" value="hmw_logout"/>
                    <input type="submit" class="hmw_btn hmw_btn-success" value="<?php echo __("Yes, I'm ready to re-login", _HMW_PLUGIN_NAME_) ?>"/>
                </form>
            </div>
            <div class="hmw_abort" style="display: inline-block;">
                <form method="POST" action="<?php echo HMW_Classes_Tools::getSettingsUrl() ?>">
                    <?php wp_nonce_field('hmw_abort', 'hmw_nonce') ?>
                    <input type="hidden" name="action" value="hmw_abort"/>
                    <input type="submit" class="hmw_btn hmw_btn-warning" value="<?php echo __("No, abort", _HMW_PLUGIN_NAME_) ?>"/>
                </form>
            </div>

        </div>
        <?php
    }


    /**
     * Get the Admin Toolbar
     * @param null $current
     * @return string
     */
    public function getAdminTabs($current = null) {
        //Add the Menu Tabs in variable if not set before
        if (!isset($this->tabs)) $this->tabs = $this->model->getTabs();

        $content = '';
        $content .= '<div class="hmw_nav d-flex flex-column bd-highlight mb-3">';
        $content .= '<div  class="m-0 p-4 font-dark text-logo"><a href="http://hidemywpghost.com/" target="_blank"><img src="' . _HMW_THEME_URL_ . 'img/logo.png" class="ml-0 mr-2" style="width:30px;"></a>' . __('Hide My WP', _HMW_PLUGIN_NAME_) . ' <span style="color: #d6cdd1">' . _HMW_VER_NAME_ . '</span></div>';
        foreach ($this->tabs as $location => $tab) {
            if ($current == $location) {
                $class = 'active';
            } else {
                $class = '';
            }
            if ($location == 'hmw_securitycheck') {
                $content .= '<a class="m-0 p-4 font-dark hmw_nav_item ' . $class . ' fa fa-' . $tab['icon'] . '" href="' . HMW_Classes_Tools::getSettingsUrl($location)  . '">';
            }else{
                $content .= '<a class="m-0 p-4 font-dark hmw_nav_item ' . $class . ' fa fa-' . $tab['icon'] . '" href="' . HMW_Classes_Tools::getSettingsUrl() . ($location <> 'hmw_permalinks' ? '-' . $location : '') . '">';
            }
            $content .= '<span>' . $tab['title'] . '</span>';
            $content .= '<span class="hmw_nav_item_description">' . $tab['description'] . '</span>';
            $content .= '</a>';
        }
        if (HMW_Classes_Tools::getOption('api_token') <> '') {
            $content .= '<div  class="m-2 p-4 hmw_nav_button"><a href="' . _HMW_ACCOUNT_SITE_ . '/api/auth/' . HMW_Classes_Tools::getOption('api_token') . '" class="btn btn-warning btn-lg rounded-0 text-white" target="_blank">' . __('My Account', _HMW_PLUGIN_NAME_) . '</a></div>';
        }
        $content .= '</div>';
        return $content;
    }

    /**
     * Called when an action is triggered
     *
     * @return void
     */
    public function action() {
        parent::action();

        if (!current_user_can('manage_options')) {
            return;
        }

        switch (HMW_Classes_Tools::getValue('action')) {
            case 'hmw_settings':


                //Save the settings
                if (!empty($_POST)) {
                    $this->model->savePermalinks($_POST);
                }

                if (!HMW_Classes_Tools::getOption('error')) {
                    //Force the rechck security notification
                    delete_option('hmw_securitycheck_time');
                    //Clear the cache if there are no errors
                    add_action('admin_footer', array('HMW_Classes_Tools', 'emptyCache'), PHP_INT_MAX);
                    //Flush the WordPress rewrites
                    add_action('admin_footer', array('HMW_Classes_Tools', 'flushWPRewrites'), PHP_INT_MAX);
                }
                //If no errors and no reconnect required
                if (!HMW_Classes_Tools::getOption('error') && !HMW_Classes_Tools::getOption('logout')) {
                    //Save the working options into backup
                    $options = HMW_Classes_Tools::getOptions();
                    foreach ($options as $key => $value) {
                        HMW_Classes_Tools::saveOptions($key, $value, true);
                    }

                    //Send email notification about the path changed
                    HMW_Classes_ObjController::getClass('HMW_Models_Rewrite')->sendEmail();
                    //Flush the changes
                    HMW_Classes_ObjController::getClass('HMW_Models_Rewrite')->flushChanges();

                    HMW_Classes_Error::setError(__('Saved'), 'success');

                }

                break;
            case 'hmw_tweakssettings':
                //Save the settings
                if (!empty($_POST)) {
                    $this->model->saveValues($_POST);
                }

                if (!HMW_Classes_Tools::getOption('error')) {
                    //Clear the cache if there are no errors
                    add_action('admin_footer', array('HMW_Classes_Tools', 'emptyCache'), PHP_INT_MAX);
                    HMW_Classes_Error::setError(__('Saved'), 'success');
                }

                break;

	        case 'hmw_mappsettings':
		        //Save the patterns as array
		        if ($hmw_text_mapping_from = HMW_Classes_Tools::getValue('hmw_text_mapping_from', false)) {
			        if ($hmw_text_mapping_to = HMW_Classes_Tools::getValue('hmw_text_mapping_to', false)) {
				        $hmw_text_mapping = array();

				        if (HMW_Classes_Tools::getOption('hmw_hide_classes')) {
					        $custom_classes = json_decode(HMW_Classes_Tools::getOption('hmw_hide_classes'), true);
					        if (!empty($custom_classes)) {
						        foreach ($custom_classes as $custom_classe) {
							        if (!in_array($custom_classe, array('wp-image', 'wp-post', 'wp-caption'))) {
								        $hmw_text_mapping['from'][] = $custom_classe;
								        $hmw_text_mapping['to'][] = '';
							        }
						        }
						        HMW_Classes_Tools::saveOptions('hmw_hide_classes', json_encode(array()));
					        }
				        }
				        foreach ($hmw_text_mapping_from as $index => $from) {
					        if ($hmw_text_mapping_from[$index] <> '' && $hmw_text_mapping_to[$index] <> '') {
						        $hmw_text_mapping_from[$index] = preg_replace('/[^A-Za-z0-9-_\/\.]/', '', $hmw_text_mapping_from[$index]);
						        $hmw_text_mapping_to[$index] = preg_replace('/[^A-Za-z0-9-_\/\.]/', '', $hmw_text_mapping_to[$index]);

						        if (!isset($hmw_text_mapping['from']) || !in_array($hmw_text_mapping_from[$index], (array)$hmw_text_mapping['from'])) {
							        //Don't save the wp-posts for Woodmart theme
							        if (HMW_Classes_Tools::isPluginActive('woocommerce/woocommerce.php')) {
								        if ($hmw_text_mapping_from[$index] == 'wp-post' || $hmw_text_mapping_from[$index] == 'wp-post-image') {
									        continue;
								        }
							        }

							        if ($hmw_text_mapping_from[$index] <> $hmw_text_mapping_to[$index]) {
								        $hmw_text_mapping['from'][] = $hmw_text_mapping_from[$index];
								        $hmw_text_mapping['to'][] = $hmw_text_mapping_to[$index];
							        }
						        } else {
							        HMW_Classes_Error::setError(__('Error: You entered the same text twice in the Text Mapping. We removed the duplicates to prevent any redirect errors.'));
						        }
					        }
				        }
				        HMW_Classes_Tools::saveOptions('hmw_text_mapping', json_encode($hmw_text_mapping));

			        }
		        }

		        //Clear the cache if there are no errors
		        if (!HMW_Classes_Tools::getOption('error')) {
			        //Clear the cache if there are no errors
			        add_action('admin_footer', array('HMW_Classes_Tools', 'emptyCache'), PHP_INT_MAX);
			        HMW_Classes_Error::setError(__('Saved'), 'success');
		        }
		        break;

            case 'hmw_advsettings':

                if (!empty($_POST)) {
                    $this->model->saveValues($_POST);

                    //Clear the cache if there are no errors
                    if (!HMW_Classes_Tools::getOption('error')) {
                        //Clear the cache if there are no errors
                        add_action('admin_footer', array('HMW_Classes_Tools', 'emptyCache'), PHP_INT_MAX);
                        HMW_Classes_Error::setError(__('Saved'), 'success');
                    }
                }

                break;

            case 'hmw_abort':
                //get the safe options from database
                HMW_Classes_Tools::$options = HMW_Classes_Tools::getOptions(true);
                //set th eprevious admin path
                HMW_Classes_Tools::saveOptions('hmw_admin_url', HMW_Classes_Tools::getOption('hmw_admin_url'));
                HMW_Classes_Tools::saveOptions('error', false);
                HMW_Classes_Tools::saveOptions('logout', false);

                //Clear the cache if there are no errors
                add_action('admin_footer', array('HMW_Classes_Tools', 'emptyCache'), PHP_INT_MAX);
                //Flush the WordPress rewrites
                add_action('admin_footer', array('HMW_Classes_Tools', 'flushWPRewrites'), PHP_INT_MAX);

                HMW_Classes_ObjController::getClass('HMW_Models_Rewrite')->clearRedirect();

                //Flush config to remove the rules
                HMW_Classes_ObjController::getClass('HMW_Models_Rewrite')->flushChanges();

                break;
            case 'hmw_savedefault':
                HMW_Classes_Tools::saveOptions('logout', false);

                $options = HMW_Classes_Tools::getOptions();
                foreach ($options as $key => $value) {
                    HMW_Classes_Tools::saveOptions($key, $value, true);
                }
                break;
            case 'hmw_logout':
                HMW_Classes_Tools::saveOptions('error', false);
                HMW_Classes_Tools::saveOptions('logout', false);

                //Send email notification about the path changed
                HMW_Classes_ObjController::getClass('HMW_Models_Rewrite')->sendEmail();

                //save to safe mode in case of db
                foreach (HMW_Classes_Tools::$options as $key => $value) {
                    HMW_Classes_Tools::saveOptions($key, $value, true);
                }

                //Force the rechck security notification
                delete_option('hmw_securitycheck_time');
                //Clear the cache if there are no errors
                add_action('admin_footer', array('HMW_Classes_Tools', 'emptyCache'), PHP_INT_MAX);
                //Flush the WordPress rewrites
                add_action('admin_footer', array('HMW_Classes_Tools', 'flushWPRewrites'), PHP_INT_MAX);

                HMW_Classes_ObjController::getClass('HMW_Models_Rewrite')->flushChanges();

                wp_logout();
                wp_redirect(site_url(HMW_Classes_Tools::getOption('hmw_login_url')));
                die();
                break;
            case 'hmw_manualrewrite':
                HMW_Classes_Tools::saveOptions('error', false);
                HMW_Classes_Tools::saveOptions('configure_error', false);

                if (!HMW_Classes_Tools::getOption('logout')) {
                    //Send email notification about the path changed
                    HMW_Classes_ObjController::getClass('HMW_Models_Rewrite')->sendEmail();

                    //Save the last safe data
                    foreach (HMW_Classes_Tools::$options as $key => $value) {
                        HMW_Classes_Tools::saveOptions($key, $value, true);
                    }
                }

                //Clear the cache if there are no errors
                add_action('admin_footer', array('HMW_Classes_Tools', 'emptyCache'), PHP_INT_MAX);
                if (HMW_Classes_Tools::isNginx() || HMW_Classes_Tools::isWpengine()) {
                    @shell_exec('nginx -s reload');
                }
                break;
            case 'hmw_configureerror':
                HMW_Classes_Tools::saveOptions('error', false);
                HMW_Classes_Tools::saveOptions('configure_error', true);

                if (!HMW_Classes_Tools::getOption('logout')) {
                    //Send email notification about the path changed
                    HMW_Classes_ObjController::getClass('HMW_Models_Rewrite')->sendEmail();

                    //Save the last safe data
                    foreach (HMW_Classes_Tools::$options as $key => $value) {
                        HMW_Classes_Tools::saveOptions($key, $value, true);
                    }
                }

                //Clear the cache if there are no errors
                add_action('admin_footer', array('HMW_Classes_Tools', 'emptyCache'), PHP_INT_MAX);
                if (HMW_Classes_Tools::isNginx() || HMW_Classes_Tools::isWpengine()) {
                    @shell_exec('nginx -s reload');
                }
                break;
            case 'hmw_connect':
                //Connect to API with the Email
                $email = sanitize_email(HMW_Classes_Tools::getValue('hmw_email', ''));
                $token = HMW_Classes_Tools::getValue('hmw_token', '');

                $redirect_to = HMW_Classes_Tools::getSettingsUrl();
                if ($token <> '') {
                    if (preg_match('/^[a-z0-9\-]{32}$/i', $token)) {
                        HMW_Classes_Tools::saveOptions('hmw_token', $token);
                        HMW_Classes_Tools::saveOptions('error', false);
                        HMW_Classes_Tools::checkApi();
                    } else {
                        HMW_Classes_Error::setError(__('ERROR! Please make sure you use a valid token to connect the plugin with WPPlugins', _HMW_PLUGIN_NAME_) . " <br /> ");
                    }
                } elseif ($email <> '') {
                    HMW_Classes_Tools::checkApi($email, $redirect_to);
                } else {
                    HMW_Classes_Error::setError(__('ERROR! Please make sure you use an email address to connect the plugin with WPPlugins', _HMW_PLUGIN_NAME_) . " <br /> ");
                }
                break;

            case 'hmw_dont_connect':
	            $redirect_to = HMW_Classes_Tools::getSettingsUrl();

	            HMW_Classes_Tools::saveOptions('hmw_token', md5(home_url()));
	            HMW_Classes_Tools::saveOptions('error', false);

	            wp_redirect($redirect_to);
	            exit();
            case 'hmw_backup':
                //Save the Settings into backup
                if (!current_user_can('manage_options')) {
                    return;
                }
                HMW_Classes_Tools::getOptions();
                HMW_Classes_Tools::setHeader('text');
                header("Content-Disposition: attachment; filename=hidemywp_backup.txt");

                if (function_exists('base64_encode')) {
                    echo base64_encode(json_encode(HMW_Classes_Tools::$options));
                } else {
                    echo json_encode(HMW_Classes_Tools::$options);
                }
                exit();
                break;
            case 'hmw_restore':
                //Restore the backup
                if (!current_user_can('manage_options')) {
                    return;
                }

                if (!empty($_FILES['hmw_options']) && $_FILES['hmw_options']['tmp_name'] <> '') {
                    $options = file_get_contents($_FILES['hmw_options']['tmp_name']);
                    try {
                        if (function_exists('base64_encode') && base64_decode($options) <> '') {
                            $options = base64_decode($options);
                        }
                        $options = json_decode($options, true);
                        if (is_array($options) && isset($options['hmw_ver'])) {
                            HMW_Classes_Tools::$options = $options;
                            HMW_Classes_Tools::saveOptions();
                            HMW_Classes_Error::setError(__('Great! The backup is restored.', _HMW_PLUGIN_NAME_) . " <br /> ", 'success');

                            if (!HMW_Classes_Tools::getOption('error')) {
                                //Clear the cache if there are no errors
                                add_action('admin_footer', array('HMW_Classes_Tools', 'emptyCache'), PHP_INT_MAX);
                                //Flush the WordPress rewrites
                                add_action('admin_footer', array('HMW_Classes_Tools', 'flushWPRewrites'), PHP_INT_MAX);
                            }

                            if (!HMW_Classes_Tools::getOption('error') && !HMW_Classes_Tools::getOption('logout')) {
                                HMW_Classes_ObjController::getClass('HMW_Models_Rewrite')->flushChanges();
                            }

                        } else {
                            HMW_Classes_Error::setError(__('Error! The backup is not valid.', _HMW_PLUGIN_NAME_) . " <br /> ");
                        }
                    } catch (Exception $e) {
                        HMW_Classes_Error::setError(__('Error! The backup is not valid.', _HMW_PLUGIN_NAME_) . " <br /> ");
                    }
                } else {
                    HMW_Classes_Error::setError(__('Error! You have to enter a previous saved backup file.', _HMW_PLUGIN_NAME_) . " <br /> ");
                }

                break;

            case 'hmw_support':
                global $current_user, $wp_version;
                $return = array();


                $line = "\n\n" . "______________________________________________________________________" . "\n";
                $versions = 'URL:' . get_bloginfo('wpurl') . ", " . 'PV: ' . HMW_VERSION . ", " . 'WPV: ' . $wp_version;
                $from = HMW_Classes_Tools::getValue('hmw_email');
                $subject = __('Hide My Wp > Question', _HMW_PLUGIN_NAME_);
                $message = HMW_Classes_Tools::getValue('hmw_message', '', true);

                if ($message <> '') {
                    $message .= $line;
                    $message .= $versions;

                    $headers[] = 'From: ' . $current_user->display_name . ' <' . $from . '>';
                    if ($response = wp_mail(_HMW_SUPPORT_EMAIL_, $subject, $message, $headers)) {
                        $return['success'] = true;
                    } else {
                        $return['error'] = true;
                    }
                } else {
                    $return['error'] = true;
                }

                HMW_Classes_Tools::setHeader('json');
                echo json_encode($return);
                exit();

        }
    }


    public function hookFooter() {
        HMW_Classes_Tools::saveOptions();
        echo '<script>var hmwQuery = {"ajaxurl": "' . admin_url('admin-ajax.php') . '","nonce": "' . wp_create_nonce(_HMW_NONCE_ID_) . '"}</script>';
    }

}
