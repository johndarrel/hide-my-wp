<?php
/**
 * Settings Class
 * Called when the plugin setting is loaded
 *
 * @file The Settings file
 * @package HMWP/Settings
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Controllers_Settings extends HMWP_Classes_FrontController
{

    /**
     * List of events/actions
     *
     * @var $listTable HMWP_Models_ListTable 
     */
    public $listTable;

    public function __construct()
    {
        parent::__construct();

        //If save settings is required, show the alert
        if (HMWP_Classes_Tools::getOption('changes') ) {
            add_action('admin_notices', array($this, 'showSaveRequires'));
        }

        if (!HMWP_Classes_Tools::getOption('hmwp_valid') ) {
            add_action('admin_notices', array($this, 'showPurchaseRequires'));
        }


        //Add the Settings class only for the plugin settings page
        add_filter('admin_body_class', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Menu'), 'addSettingsClass'));

	    //If the option to prevent broken layout is on
	    if(HMWP_Classes_Tools::getOption( 'prevent_slow_loading' )){

		    //check the frontend on settings successfully saved
		    add_action('hmwp_confirmed_settings', function () {
			    //check the frontend and prevent from showing brake websites
			    $url = _HMWP_URL_ . '/view/assets/img/logo.png?hmwp_preview=1&test=' . mt_rand(11111,99999);
			    $url = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace_url($url);
			    $response = HMWP_Classes_Tools::hmwp_localcall($url,  array('redirection' => 0, 'cookies' => false));

			    //If the plugin logo is not loading correctly, switch off the path changes
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 404) {
				    HMWP_Classes_Tools::saveOptions('file_mappings', array(home_url()));
			    }
		    });

	    }

        //save the login path on Cloud
        add_action( 'hmwp_apply_permalink_changes', function () {
            HMWP_Classes_Tools::sendLoginPathsApi();
        } );

    }

    /**
     * Called on Menu hook
     * Init the Settings page
     *
     * @return void
     * @throws Exception
     */
    public function init()
    {
        /////////////////////////////////////////////////
        //Get the current Page
        $page = HMWP_Classes_Tools::getValue('page');

        if (strpos($page, '_') !== false ) {
            $tab = substr($page, (strpos($page, '_') + 1));

            if (method_exists($this, $tab)) {
                call_user_func(array($this, $tab));
            }
        }
        /////////////////////////////////////////////////

        //We need that function so make sure is loaded
        if (!function_exists('is_plugin_active_for_network') ) {
            include_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        if (HMWP_Classes_Tools::isNginx() && HMWP_Classes_Tools::getOption('test_frontend') && HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default' ) {
            $config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();
            HMWP_Classes_Error::setNotification(sprintf(esc_html__("NGINX detected. In case you didn't add the code in the NGINX config already, please add the following line. %s", 'hide-my-wp'), '<br /><br /><code><strong>include ' . $config_file . ';</strong></code> <br /><br /><strong><a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/how-to-setup-hide-my-wp-on-nginx-server/" target="_blank" >' . esc_html__("Learn how to setup on Nginx server", 'hide-my-wp') . ' >></a></strong>'), 'notice', false);
        }

        //Setting Alerts based on Logout and Error statements
        if (get_transient('hmwp_restore') == 1 ) {
            $restoreLink = '<a href="'.add_query_arg(array('hmwp_nonce' => wp_create_nonce('hmwp_restore_settings'), 'action' => 'hmwp_restore_settings')) .'" class="btn btn-default btn-sm ml-3" />' . esc_html__("Restore Settings", 'hide-my-wp'). '</a>';
            HMWP_Classes_Error::setNotification(esc_html__('Do you want to restore the last saved settings?', 'hide-my-wp') . $restoreLink);
        }

        //Show the config rules to make sure they are okay
        if (HMWP_Classes_Tools::getValue('hmwp_config') ) {
            //Initialize WordPress Filesystem
            $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

            $config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();
            if ($config_file <> '' && $wp_filesystem->exists($config_file) ) {
                $rules = $wp_filesystem->get_contents(HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile());
                HMWP_Classes_Error::setNotification('<pre>' . $rules . '</pre>');
            }

	        HMWP_Classes_Error::setNotification('<pre>' . print_r($_SERVER,true) . '</pre>');
        }

        //Load the css for Settings
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('popper');

        if (is_rtl() ) {
            HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('bootstrap.rtl');
            HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('rtl');
        } else {
            HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('bootstrap');
        }

        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('bootstrap-select');
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('font-awesome');
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('switchery');
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('alert');
        HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('settings');

        //Show connect for activation
        if (!HMWP_Classes_Tools::getOption('hmwp_token')) {
            $this->show('Connect');
            return;
        }

        if (HMWP_Classes_Tools::getOption('error') ) {
            HMWP_Classes_Error::setNotification(esc_html__('There is a configuration error in the plugin. Please Save the settings again and follow the instruction.', 'hide-my-wp'));
        }

        if (HMWP_Classes_Tools::isWpengine() ) {
            add_filter('hmwp_option_hmwp_mapping_url_show', "__return_false");
        }

        //Check compatibilities with other plugins
        HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->getAlerts();

        //Show errors on top
        HMWP_Classes_ObjController::getClass('HMWP_Classes_Error')->hookNotices();

        echo '<noscript><div class="alert-danger text-center py-3">'. sprintf(esc_html__("Javascript is disabled on your browser! You need to activate the javascript in order to use %s plugin.", 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name')) .'</div></noscript>';
        $this->show(ucfirst(str_replace('hmwp_', '', $page)));
        $this->show('blocks/Upgrade');

    }

    /**
     * Log the user event
     *
     * @throws Exception
     */
    public function log()
    {
        $this->listTable = HMWP_Classes_ObjController::getClass('HMWP_Models_ListTable');

        if (apply_filters('hmwp_showlogs', true) ) {

            $args = $urls = array();
            $args['search'] =  HMWP_Classes_Tools::getValue('s', false);
            //If it's multisite
            if(is_multisite()) {
                if (function_exists('get_sites') && class_exists('WP_Site_Query') ) {
                    $sites = get_sites();
                    if(!empty($sites)) {
                        foreach ($sites as $site) {
                            $urls[] = (_HMWP_CHECK_SSL_ ? 'https://' : 'http://') . rtrim($site->domain . $site->path, '/');
                        }
                    }
                }
            }else{
                $urls[] = home_url();
            }
            //pack the urls
            $args['urls'] = json_encode(array_unique($urls));

            //Set the log table data
            $logs = HMWP_Classes_Tools::hmwp_remote_get(_HMWP_API_SITE_ . '/api/log', $args);

            if ($logs = json_decode($logs, true)) {

                if (isset($logs['data']) && !empty($logs['data'])) {
                    $logs = $logs['data'];
                } else {
                    $logs = array();
                }

            } else {
                $logs = array();
            }

            $this->listTable->setData($logs);
        }

    }

    /**
     * Load media header
     */
    public function hookHead()
    { 
    }

    /**
     * Show this message to notify the user when to update th esettings
     *
     * @return void
     * @throws Exception
     */
    public function showSaveRequires()
    {
        if (HMWP_Classes_Tools::getOption('hmwp_hide_plugins') || HMWP_Classes_Tools::getOption('hmwp_hide_themes') ) {
            global $pagenow;
            if ($pagenow == 'plugins.php' ) {

                HMWP_Classes_ObjController::getClass('HMWP_Classes_DisplayController')->loadMedia('alert');

                ?>
                <div class="hmwp_notice error notice" style="margin-left: 0;">
                    <div style="display: inline-block;">
                        <form action="<?php echo HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks') ?>" method="POST">
                            <?php wp_nonce_field('hmwp_newpluginschange', 'hmwp_nonce') ?>
                            <input type="hidden" name="action" value="hmwp_newpluginschange"/>
                            <p>
                                <?php echo sprintf(esc_html__("New Plugin/Theme detected! You need to save the %s Setting again to include them all! %sClick here%s", 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name'), '<button type="submit" style="color: blue; text-decoration: underline; cursor: pointer; background: none; border: none;">', '</button>'); ?>
                            </p>
                        </form>

                    </div>
                </div>
                <?php
            }
        }
    }

    public function showPurchaseRequires()
    {
        global $pagenow;

        $expires = (int)HMWP_Classes_Tools::getOption('hmwp_expires');

        if ($expires > 0 ) {
            $error = sprintf(esc_html__("Your %s %s license expired on %s %s. To keep your website security up to date please make sure you have a valid subscription on %saccount.hidemywpghost.com%s", 'hide-my-wp'), '<strong>', HMWP_Classes_Tools::getOption('hmwp_plugin_name'), date('d M Y', $expires), '</strong>', '<a href="' . HMWP_Classes_Tools::getCloudUrl('orders') . '" style="line-height: 30px;" target="_blank">', '</a>');

            if ($pagenow == 'plugins.php' || $pagenow == 'index.php') {
                ?>
                <div class="col-sm-12 mx-0 hmwp_notice error notice">
                    <div style="display: inline-block;"><p> <?php echo esc_html($error) ?> </p></div>
                </div>
                <?php
            } else {
                HMWP_Classes_Error::setNotification($error);
            }
        }
    }

    /**
     * Get the Admin Toolbar
     *
     * @param  null $current
     * @return string $content
     * @throws Exception
     */
    public function getAdminTabs( $current = null )
    {
        //Add the Menu Sub Tabs in the selected page
        $subtabs = HMWP_Classes_ObjController::getClass('HMWP_Models_Menu')->getSubMenu($current);

        $content = '<div class="hmwp_nav d-flex flex-column bd-highlight mb-3">';
        $content .= '<div  class="m-0 px-3 py-4 font-dark font-weight-bold text-logo"><a href="'.esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_website')).'" target="_blank"><img src="' . esc_url(HMWP_Classes_Tools::getOption('hmwp_plugin_logo') ? HMWP_Classes_Tools::getOption('hmwp_plugin_logo') :  _HMWP_ASSETS_URL_ . 'img/logo.png') . '" class="ml-0 mr-2" style="height:35px; max-width: 180px;" alt=""></a></div>';
        //$content .= '<ul>';
        foreach ( $subtabs as $tab ) {
            $content .= '<a href="#' . esc_attr($tab['tab']) . '" class="m-0 px-3 py-3 font-dark hmwp_nav_item" data-tab="' . esc_attr($tab['tab']) . '">' . wp_kses_post($tab['title']) . '</a>';
        }

        $content .= '</div>';

        return $content;
    }

    /**
     * Called when an action is triggered
     *
     * @throws Exception
     */
    public function action()
    {
        parent::action();

        if (!HMWP_Classes_Tools::userCan('hmwp_manage_settings') ) {
            return;
        }

        switch ( HMWP_Classes_Tools::getValue('action') ) {
        case 'hmwp_settings':

            //Save the settings
            if (!empty($_POST) ) {
                /**  @var $this->model HMWP_Models_Settings  */
                $this->model->savePermalinks($_POST);
            }

	        //whitelist_ip
            $this->saveWhiteListIps();

	        //load the after saving settings process
	        if($this->applyPermalinksChanged()){
		        HMWP_Classes_Error::setNotification(esc_html__('Saved'), 'success');

		        //add action for later use
		        do_action( 'hmwp_settings_saved' );
	        }

            break;
        case 'hmwp_tweakssettings':

            //Save the settings
            if (!empty($_POST) ) {
                $this->model->saveValues($_POST);
            }

            HMWP_Classes_Tools::saveOptions('hmwp_disable_click_message', HMWP_Classes_Tools::getValue('hmwp_disable_click_message', '', true));
            HMWP_Classes_Tools::saveOptions('hmwp_disable_inspect_message', HMWP_Classes_Tools::getValue('hmwp_disable_inspect_message', '', true));
            HMWP_Classes_Tools::saveOptions('hmwp_disable_source_message', HMWP_Classes_Tools::getValue('hmwp_disable_source_message', '', true));
            HMWP_Classes_Tools::saveOptions('hmwp_disable_copy_paste_message', HMWP_Classes_Tools::getValue('hmwp_disable_copy_paste_message', '', true));
            HMWP_Classes_Tools::saveOptions('hmwp_disable_drag_drop_message', HMWP_Classes_Tools::getValue('hmwp_disable_drag_drop_message', '', true));

	        //load the after saving settings process
	        if($this->applyPermalinksChanged()){
		        HMWP_Classes_Error::setNotification(esc_html__('Saved'), 'success');

		        //add action for later use
		        do_action('hmwp_tweakssettings_saved');
	        }

            break;
        case 'hmwp_mappsettings':
            //Save Mapping for classes and ids
            HMWP_Classes_Tools::saveOptions('hmwp_mapping_classes', HMWP_Classes_Tools::getValue('hmwp_mapping_classes'));
            HMWP_Classes_Tools::saveOptions('hmwp_mapping_file', HMWP_Classes_Tools::getValue('hmwp_mapping_file'));
            HMWP_Classes_Tools::saveOptions('hmwp_file_cache', HMWP_Classes_Tools::getValue('hmwp_file_cache'));

            //Save the patterns as array
            //Save CDN URLs
            if ($urls = HMWP_Classes_Tools::getValue('hmwp_cdn_urls') ) {
                $hmwp_cdn_urls = array();
                foreach ( $urls as $row ) {
                    if ($row <> '' ) {
                        $row = preg_replace('/[^A-Za-z0-9-_.:\/]/', '', $row);
                        if ($row <> '' ) {
                            $hmwp_cdn_urls[] = $row;
                        }
                    }
                }
                HMWP_Classes_Tools::saveOptions('hmwp_cdn_urls', json_encode($hmwp_cdn_urls));
            }

	        //Save Text Mapping
	        if ($hmwp_text_mapping_from = HMWP_Classes_Tools::getValue('hmwp_text_mapping_from') ) {
		        if ($hmwp_text_mapping_to = HMWP_Classes_Tools::getValue('hmwp_text_mapping_to') ) {
			        $this->model->saveTextMapping($hmwp_text_mapping_from, $hmwp_text_mapping_to);
		        }
	        }

	        //Save URL mapping
	        if ($hmwp_url_mapping_from = HMWP_Classes_Tools::getValue('hmwp_url_mapping_from') ) {
		        if ($hmwp_url_mapping_to = HMWP_Classes_Tools::getValue('hmwp_url_mapping_to') ) {
			        $this->model->saveURLMapping($hmwp_url_mapping_from, $hmwp_url_mapping_to);
		        }
	        }

	        //load the after saving settings process
	        if($this->applyPermalinksChanged()) {
		        HMWP_Classes_Error::setNotification(esc_html__('Saved'), 'success');

		        //add action for later use
		        do_action('hmwp_mappsettings_saved');

	        }

            break;
        case 'hmwp_advsettings':

            if (!empty($_POST) ) {
                $this->model->saveValues($_POST);

                //save the loading moment
                HMWP_Classes_Tools::saveOptions('hmwp_firstload', in_array('first', HMWP_Classes_Tools::getOption('hmwp_loading_hook')));
                HMWP_Classes_Tools::saveOptions('hmwp_priorityload', in_array('priority', HMWP_Classes_Tools::getOption('hmwp_loading_hook')));
                HMWP_Classes_Tools::saveOptions('hmwp_laterload', in_array('late', HMWP_Classes_Tools::getOption('hmwp_loading_hook')));

                //Send the notification email in case of Weekly report
                if (HMWP_Classes_Tools::getValue('hmwp_send_email') && HMWP_Classes_Tools::getValue('hmwp_email_address') ) {
                    $args = array( 'email' => HMWP_Classes_Tools::getValue('hmwp_email_address')  );
                    HMWP_Classes_Tools::hmwp_remote_post(_HMWP_ACCOUNT_SITE_ . '/api/log/settings', $args, array('timeout' => 5));
                }

                if (HMWP_Classes_Tools::getOption('hmwp_firstload') ) {
                    //Add the must-use plugin to force loading before all others plugins
                    HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->addMUPlugin();
                }else{
                    HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->deleteMUPlugin();
                }

	            //load the after saving settings process
	            if($this->applyPermalinksChanged()) {
		            HMWP_Classes_Error::setNotification(esc_html__('Saved'), 'success');

		            //add action for later use
		            do_action('hmwp_advsettings_saved');

	            }

            }

            break;
        case 'hmwp_savecachepath':

            //Save the option to change the paths in the cache file
            HMWP_Classes_Tools::saveOptions('hmwp_change_in_cache', HMWP_Classes_Tools::getValue('hmwp_change_in_cache'));

            //Save the cache directory
            $directory = HMWP_Classes_Tools::getValue('hmwp_change_in_cache_directory');

            if($directory <> '') {
                $directory = trim($directory, '/');

                //Remove subdirs
                if (strpos($directory, '/') !== false) {
                    $directory = substr($directory, 0, strpos($directory, '/'));
                }

                if (!in_array($directory, array('languages', 'mu-plugins', 'plugins', 'themes', 'upgrade', 'uploads'))) {
                    HMWP_Classes_Tools::saveOptions('hmwp_change_in_cache_directory', $directory);
                } else {
                    wp_send_json_error(esc_html__('Path not allowed. Avoid paths like plugins and themes.', 'hide-my-wp'));
                }
            }else{
                HMWP_Classes_Tools::saveOptions('hmwp_change_in_cache_directory', '');
            }

            if(HMWP_Classes_Tools::isAjax()){
                wp_send_json_success(esc_html__('Saved', 'hide-my-wp'));
            }
            break;

        case 'hmwp_devsettings':

            //Set dev settings
            HMWP_Classes_Tools::saveOptions('hmwp_debug', HMWP_Classes_Tools::getValue('hmwp_debug'));

            break;
        case 'hmwp_devdownload':
            //Initialize WordPress Filesystem
            $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

            //Set header as text
            HMWP_Classes_Tools::setHeader('text');
            $filename = preg_replace('/[-.]/', '_', parse_url(home_url(), PHP_URL_HOST));
            header("Content-Disposition: attachment; filename=" . $filename . "_hidemywp_debug.txt");

            if (function_exists('glob') ) {
                $pattern = _HMWP_CACHE_DIR_ . '*.log';
                $files = glob($pattern, 0);
                if (!empty($files) ) {
                    foreach ( $files as $file ) {
                        echo basename($file) . PHP_EOL;
                        echo "---------------------------" . PHP_EOL;
                        echo $wp_filesystem->get_contents($file) . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL;
                    }
                }
            }

            exit();
        case 'hmwp_ignore_errors':
            //Empty WordPress rewrites count for 404 error.
            //This happens when the rules are not saved through config file
            HMWP_Classes_Tools::saveOptions('file_mappings', array());

            break;
        case 'hmwp_abort':
        case 'hmwp_restore_settings':
            //get current user tokens
            $hmwp_token = HMWP_Classes_Tools::getOption('hmwp_token');
            $api_token = HMWP_Classes_Tools::getOption('api_token');
            //get the safe options from database
            HMWP_Classes_Tools::$options = HMWP_Classes_Tools::getOptions(true);
            //set the current user tokens
            HMWP_Classes_Tools::saveOptions('hmwp_token', $hmwp_token);
            HMWP_Classes_Tools::saveOptions('api_token', $api_token);

            //set frontend, error & logout to false
            HMWP_Classes_Tools::saveOptions('test_frontend', false);
	        HMWP_Classes_Tools::saveOptions('file_mappings', array());
	        HMWP_Classes_Tools::saveOptions('error', false);
            HMWP_Classes_Tools::saveOptions('logout', false);

	        //load the after saving settings process
	        $this->applyPermalinksChanged(true);

            break;
        case 'hmwp_newpluginschange':
            //reset the change notification
            HMWP_Classes_Tools::saveOptions('changes', 0);
            remove_action('admin_notices', array($this, 'showSaveRequires'));

            //generate unique names for plugins if needed
            if (HMWP_Classes_Tools::getOption('hmwp_hide_plugins') ) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->hidePluginNames();
            }
            if (HMWP_Classes_Tools::getOption('hmwp_hide_themes') ) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->hideThemeNames();
            }

	        //load the after saving settings process
	        if($this->applyPermalinksChanged()) {
		        HMWP_Classes_Error::setNotification(esc_html__('The list of plugins and themes was updated with success!'), 'success');
	        }

            break;
        case 'hmwp_confirm':
            HMWP_Classes_Tools::saveOptions('error', false);
            HMWP_Classes_Tools::saveOptions('logout', false);
            HMWP_Classes_Tools::saveOptions('test_frontend', false);
	        HMWP_Classes_Tools::saveOptions('file_mappings', array());

            //Send email notification about the path changed
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->sendEmail();

            //save to safe mode in case of db
            if (!HMWP_Classes_Tools::getOption('logout') ) {
                HMWP_Classes_Tools::saveOptionsBackup();
            }

            //Force the rechck security notification
            delete_option(HMWP_SECURITY_CHECK_TIME);

            HMWP_Classes_Tools::saveOptions('download_settings', true);

	        //add action for later use
	        do_action('hmwp_confirmed_settings');

            break;
        case 'hmwp_manualrewrite':
            HMWP_Classes_Tools::saveOptions('error', false);
            HMWP_Classes_Tools::saveOptions('logout', false);
            HMWP_Classes_Tools::saveOptions('test_frontend', true);
	        HMWP_Classes_Tools::saveOptions('file_mappings', array());

            //save to safe mode in case of db
            if (!HMWP_Classes_Tools::getOption('logout') ) {
                HMWP_Classes_Tools::saveOptionsBackup();
            }

            //Clear the cache if there are no errors
            HMWP_Classes_Tools::emptyCache();

            if (HMWP_Classes_Tools::isNginx() ) {
                @shell_exec('nginx -s reload');
            }

            break;
        case 'hmwp_changepathsincache':
            //Check the cache plugin
            HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->checkCacheFiles();

            HMWP_Classes_Error::setNotification(esc_html__('Paths changed in the existing cache files', 'hide-my-wp'), 'success');
            break;
        case 'hmwp_backup':
            //Save the Settings into backup
            if (!HMWP_Classes_Tools::userCan('hmwp_manage_settings') ) {
                return;
            }
            HMWP_Classes_Tools::getOptions();
            HMWP_Classes_Tools::setHeader('text');
            $filename = preg_replace('/[-.]/', '_', parse_url(home_url(), PHP_URL_HOST));
            header("Content-Disposition: attachment; filename=" . $filename . "_hidemywp_backup.txt");

            if (function_exists('base64_encode') ) {
                echo base64_encode(json_encode(HMWP_Classes_Tools::$options));
            }
            exit();
        case 'hmwp_rollback':

            $hmwp_token = HMWP_Classes_Tools::getOption('hmwp_token');
            $api_token = HMWP_Classes_Tools::getOption('api_token');

            $options = HMWP_Classes_Tools::$default;
            //Prevent duplicates
            foreach ( $options as $key => $value ) {
                //set the default params from tools
                HMWP_Classes_Tools::saveOptions($key, $value);
                HMWP_Classes_Tools::saveOptions('hmwp_token', $hmwp_token);
                HMWP_Classes_Tools::saveOptions('api_token', $api_token);
            }

            //remove the custom rules
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeToFile('', 'HMWP_VULNERABILITY');
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeToFile('', 'HMWP_RULES');

            HMWP_Classes_Error::setNotification(esc_html__('Great! The initial values are restored.', 'hide-my-wp') . " <br /> ", 'success');

            break;
	    case 'hmwp_rollback_stable':
		    HMWP_Classes_Tools::setHeader('html');
		        $plugin_slug = 'hide-my-wp';
		        $rollback = HMWP_Classes_ObjController::getClass('HMWP_Models_Rollback');

		        $rollback->set_plugin(
			        array(
				        'version' => HMWP_STABLE_VERSION,
				        'plugin_name' => _HMWP_ROOT_DIR_,
				        'plugin_slug' => $plugin_slug,
				        'package_url' => sprintf('https://downloads.wordpress.org/plugin/%s.%s.zip', $plugin_slug, HMWP_STABLE_VERSION),
			        )
		        );

		        $rollback->run();

		        wp_die(
			        '', esc_html__("Rollback to Previous Version", 'hide-my-wp'), [
				        'response' => 200,
			        ]
		        );
        case 'hmwp_restore':

            //Initialize WordPress Filesystem
            $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

            //Restore the backup
            if (!HMWP_Classes_Tools::userCan('hmwp_manage_settings') ) {
                return;
            }

            if (!empty($_FILES['hmwp_options']) && $_FILES['hmwp_options']['tmp_name'] <> '' ) {
                $options = $wp_filesystem->get_contents($_FILES['hmwp_options']['tmp_name']);
                try {
                    if (function_exists('base64_encode') && base64_decode($options) <> '' ) {
                        $options = base64_decode($options);
                    }
                    $options = json_decode($options, true);

                    if (is_array($options) && isset($options['hmwp_ver']) ) {

                        foreach ( $options as $key => $value ) {
                            if ($key <> 'hmwp_token' && $key <> 'api_token' ) {
                                HMWP_Classes_Tools::saveOptions($key, $value);
                            }
                        }

	                    //load the after saving settings process
	                    if($this->applyPermalinksChanged(true)){
		                    HMWP_Classes_Error::setNotification(esc_html__('Great! The backup is restored.', 'hide-my-wp') . " <br /> ", 'success');
	                    }

                    } else {
                        HMWP_Classes_Error::setNotification(esc_html__('Error! The backup is not valid.', 'hide-my-wp') . " <br /> ");
                    }
                } catch ( Exception $e ) {
                    HMWP_Classes_Error::setNotification(esc_html__('Error! The backup is not valid.', 'hide-my-wp') . " <br /> ");
                }
            } else {
                HMWP_Classes_Error::setNotification(esc_html__('Error! No backup to restore.', 'hide-my-wp'));
            }
            break;

        case 'hmwp_download_settings':
            //Save the Settings into backup
            if (!HMWP_Classes_Tools::userCan('hmwp_manage_settings') ) {
                return;
            }

            HMWP_Classes_Tools::saveOptions('download_settings', false);

            HMWP_Classes_Tools::getOptions();
            HMWP_Classes_Tools::setHeader('text');
            $filename = preg_replace('/[-.]/', '_', parse_url(home_url(), PHP_URL_HOST));
            header("Content-Disposition: attachment; filename=" . $filename . "_hidemywp_login.txt");

            $line = "\n" . "________________________________________" . PHP_EOL;
            $message = sprintf(esc_html__("Thank you for using %s!", 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name')) . PHP_EOL;
            $message .= $line;
            $message .= esc_html__("Your new site URLs are", 'hide-my-wp') . ':' . PHP_EOL . PHP_EOL;
            $message .= esc_html__("Admin URL", 'hide-my-wp') . ': ' . admin_url() . PHP_EOL;
            $message .= esc_html__("Login URL", 'hide-my-wp') . ': '  . site_url(HMWP_Classes_Tools::$options['hmwp_login_url']) . PHP_EOL;
            $message .= $line;
            $message .= esc_html__("Note: If you can't login to your site, just access this URL", 'hide-my-wp') . ':' . PHP_EOL . PHP_EOL;
            $message .= site_url() . "/wp-login.php?" . HMWP_Classes_Tools::getOption('hmwp_disable_name') . "=" . HMWP_Classes_Tools::$options['hmwp_disable'] . PHP_EOL . PHP_EOL;
            $message .= $line;
            $message .= esc_html__("Best regards", 'hide-my-wp') . ',' . PHP_EOL;
            $message .= HMWP_Classes_Tools::getOption('hmwp_plugin_name') . PHP_EOL;

            //Echo the new paths in a txt file
            echo $message;
            exit();
        }

    }

	/**
     * Save the whitelist IPs into database
	 * @return void
	 */
    private function saveWhiteListIps(){

	    $whitelist = HMWP_Classes_Tools::getValue('whitelist_ip', '', true);
	    $ips = explode(PHP_EOL, $whitelist);

	    if (!empty($ips)) {
		    foreach ($ips as &$ip) {
			    $ip = trim($ip);

			    // Check for IPv4 IP cast as IPv6
			    if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/', $ip, $matches)) {
				    $ip = $matches[1];
			    }
		    }

		    $ips = array_unique($ips);
		    HMWP_Classes_Tools::saveOptions('whitelist_ip', json_encode($ips));
	    }
    }

    /**
     * This function applies changes to permalinks.
     * It deletes the restore transient and clears the cache if there are no errors.
     * If no changes are made on settings and $force is false, the function returns true.
     * It forces the recheck security notification, clears the cache, removes the redirects, and flushes the WordPress rewrites.
     * If there are no errors, it checks if there is any main path change and saves the working options into backup.
     * It sends an email notification about the path changed, sets the cookies for the current path, activates frontend test, and triggers an action after applying the permalink changes.
     *
     * @param bool $force If true, the function will always apply the permalink changes.
     * @return bool Returns true if the changes are applied successfully; otherwise, returns false.
     *
     * @throws Exception
     */
    private function applyPermalinksChanged($force = false){

        // Delete the restore transient
        delete_transient('hmwp_restore');

        //Clear the cache if there are no errors
        if (HMWP_Classes_Tools::getOption('error') ) {
            return false;
        }

        //If no change is made on settings, just return
        if(!$force && !$this->model->checkOptionsChange()) {
            return true;
        }

        //Force the recheck security notification
        delete_option(HMWP_SECURITY_CHECK_TIME);

        //Clear the cache and remove the redirects
        HMWP_Classes_Tools::emptyCache();

        //Flush the WordPress rewrites
        HMWP_Classes_Tools::flushWPRewrites();

        //check if the config file is writable or is WP-engine server
        if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->isConfigWritable() || HMWP_Classes_Tools::isWpengine()) {
            //if not writeable, call the rules to show manually changes
            //show rules to be added manually
            if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->clearRedirect()->setRewriteRules()->flushRewrites()) {
                HMWP_Classes_Tools::saveOptions('test_frontend', false);
                HMWP_Classes_Tools::saveOptions('error', true);
            }
        }else{
            //Flush the changes
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();
        }

        //If there are no errors
        if (!HMWP_Classes_Error::isError() ) {

            //Check if there is any main path change
            $this->model->checkMainPathsChange();

            if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) {
                //Save the working options into backup
                HMWP_Classes_Tools::saveOptionsBackup();
            }

            //Redirect to the new admin URL
            if ( HMWP_Classes_Tools::getOption( 'logout' ) ) {

                //Send email notification about the path changed
                HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->sendEmail();

                //Set the cookies for the current path
                $cookies = HMWP_Classes_ObjController::newInstance( 'HMWP_Models_Cookies' );

                if ( HMWP_Classes_Tools::isNginx() || $cookies->setCookiesCurrentPath() ) {

                    HMWP_Classes_Tools::saveOptions( 'logout', false );
                    //activate frontend test
                    HMWP_Classes_Tools::saveOptions( 'test_frontend', true );

                    remove_all_filters( 'wp_redirect' );
                    remove_all_filters( 'admin_url' );

                    //trigger action after apply the permalink changes
                    do_action('hmwp_apply_permalink_changes');

                    wp_redirect(HMWP_Classes_Tools::getSettingsUrl(HMWP_Classes_Tools::getValue('page')));
                    exit();
                }

            }

            //trigger action after apply the permalink changes
            do_action('hmwp_apply_permalink_changes');

            return true;
        }

        return false;
    }

    /**
     * If javascript is not loaded
     * @return void
     */
    public function hookFooter()
    {
        echo '<noscript><style>.tab-panel {display: block;}</style></noscript>';
    }

}
