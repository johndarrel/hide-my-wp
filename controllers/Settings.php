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
            HMWP_Classes_Error::setError(sprintf(esc_html__("NGINX detected. In case you didn't add the code in the NGINX config already, please add the following line. %s", 'hide-my-wp'), '<br /><br /><code><strong>include ' . $config_file . ';</strong></code> <br /><br /><h5>' . esc_html__("Don't forget to reload the Nginx service.", 'hide-my-wp') . ' ' . '</h5><strong><br /><a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/how-to-setup-hide-my-wp-on-nginx-server/" target="_blank" style="color: red">' . esc_html__("Learn how to setup on Nginx server", 'hide-my-wp') . '</a></strong>'), 'notice', false);
        }


        //Setting Alerts based on Logout and Error statements
        if (get_transient('hmwp_restore') == 1 ) {
            $restoreLink = '<a href="'.add_query_arg(array('hmwp_nonce' => wp_create_nonce('hmwp_restore_settings'), 'action' => 'hmwp_restore_settings')) .'" class="btn btn-default btn-sm ml-3" />' . esc_html__("Restore Settings", 'hide-my-wp'). '</a>';
            HMWP_Classes_Error::setError(esc_html__('Do you want to restore the last saved settings?', 'hide-my-wp') . $restoreLink);
        }

        //Show the config rules to make sure they are okay
        if (HMWP_Classes_Tools::getValue('hmwp_config') ) {
            //Initialize WordPress Filesystem
            $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

            $config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();
            if ($config_file <> '' && $wp_filesystem->exists($config_file) ) {
                $rules = $wp_filesystem->get_contents(HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile());
                HMWP_Classes_Error::setError('<pre>' . $rules . '</pre>');
            }

	        HMWP_Classes_Error::setError('<pre>' . print_r($_SERVER,true) . '</pre>');
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
            HMWP_Classes_Error::setError(esc_html__('There is a configuration error in the plugin. Please Save the settings again and follow the instruction.', 'hide-my-wp'));
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
                        <form action="<?php echo HMWP_Classes_Tools::getSettingsUrl() ?>" method="POST">
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
                HMWP_Classes_Error::setError($error);
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
        $content .= '<div  class="m-0 px-3 py-4 font-dark font-weight-bold text-logo"><a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'" target="_blank"><img src="' . (HMWP_Classes_Tools::getOption('hmwp_plugin_logo') ? HMWP_Classes_Tools::getOption('hmwp_plugin_logo') :  _HMWP_ASSETS_URL_ . 'img/logo.png') . '" class="ml-0 mr-2" style="height:35px; max-width: 180px;" alt=""></a></div>';
        //$content .= '<ul>';
        foreach ( $subtabs as $tab ) {
            $content .= '<a href="#' . $tab['tab'] . '" class="m-0 px-3 py-3 font-dark hmwp_nav_item" data-tab="' . $tab['tab'] . '">' . $tab['title'] . '</a>';
        }

        $content .= '</div>';

        return wp_kses_post($content);
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

            //If no change is made on settings, just return
            if(!$this->model->checkOptionsChange()) {
                return;
            }

            //Create the Wp-Rocket Burts Mapping for all blogs if not exists
            HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->rocket_burst_mapping();


            //If no errors and no reconnect required
            if (!HMWP_Classes_Tools::getOption('error') ) {

                // Delete the restore transient
                delete_transient('hmwp_restore');
                //Force the recheck security notification
                delete_option(HMWP_SECURITY_CHECK_TIME);
                //Clear the cache if there are no errors
                HMWP_Classes_Tools::emptyCache();
                //Flush the WordPress rewrites
                HMWP_Classes_Tools::flushWPRewrites();

                //Flush the changes
                HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();

                //If there are no errors
                if (!HMWP_Classes_Error::isError() ) {

                    if (!HMWP_Classes_Tools::getOption('logout') || HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' ) {
                        //Save the working options into backup
                        HMWP_Classes_Tools::saveOptionsBackup();
                    }

                    //Send email notification about the path changed
                    HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->sendEmail();

                    HMWP_Classes_Error::setError(esc_html__('Saved'), 'success');

                    //Show the Nginx message to set up the config file
                    if (HMWP_Classes_Tools::isNginx() && !HMWP_Classes_Tools::getOption('test_frontend') && HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default' ) {
                        $config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();
                        HMWP_Classes_Error::setError(sprintf(esc_html__("NGINX detected. In case you didn't add the code in the NGINX config already, please add the following line. %s", 'hide-my-wp'), '<br /><br /><code><strong>include ' . $config_file . ';</strong></code> <br /><br /><h5>' . esc_html__("Don't forget to reload the Nginx service.", 'hide-my-wp') . ' ' . '</h5><strong><br /><a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/how-to-setup-hide-my-wp-on-nginx-server/" target="_blank" style="color: red">' . esc_html__("Learn how to setup on Nginx server", 'hide-my-wp') . '</a></strong>'), 'notice', false);
                    }

                    //Redirect to the new admin URL
                    if (HMWP_Classes_Tools::getOption('logout') ) {

                        //Set the cookies for the current path
                        $cookies = HMWP_Classes_ObjController::newInstance('HMWP_Models_Cookies');

                        if (HMWP_Classes_Tools::isNginx() || $cookies->setCookiesCurrentPath() ) {
                            //set logout to false
                            HMWP_Classes_Tools::saveOptions('logout', false);
                            //activate frontend test
                            HMWP_Classes_Tools::saveOptions('test_frontend', true);

                            remove_all_filters('wp_redirect');
                            remove_all_filters('admin_url');
                            wp_safe_redirect(HMWP_Classes_Tools::getSettingsUrl(HMWP_Classes_Tools::getValue('page')));
                            exit();
                        }
                    }
                }
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

            //If no change is made on settings, just return
            if(!$this->model->checkOptionsChange()) {
                return;
            }

            //Flush the changes for XML-RPC option
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();

            if (!HMWP_Classes_Tools::getOption('error') ) {

                if (!HMWP_Classes_Tools::getOption('logout') ) {
                    HMWP_Classes_Tools::saveOptionsBackup();
                }

                HMWP_Classes_Error::setError(esc_html__('Saved'), 'success');
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
            if ($hmwp_text_mapping_from = HMWP_Classes_Tools::getValue('hmwp_text_mapping_from', false) ) {
                if ($hmwp_text_mapping_to = HMWP_Classes_Tools::getValue('hmwp_text_mapping_to', false) ) {
                    $hmwp_text_mapping = array();

                    foreach ( $hmwp_text_mapping_from as $index => $from ) {
                        if ($hmwp_text_mapping_from[$index] <> '' && $hmwp_text_mapping_to[$index] <> '' ) {
                            $hmwp_text_mapping_from[$index] = preg_replace('/[^A-Za-z0-9-_.{}\/]/', '', $hmwp_text_mapping_from[$index]);
                            $hmwp_text_mapping_to[$index] = preg_replace('/[^A-Za-z0-9-_.{}\/]/', '', $hmwp_text_mapping_to[$index]);

                            if (!isset($hmwp_text_mapping['from']) || !in_array($hmwp_text_mapping_from[$index], (array)$hmwp_text_mapping['from']) ) {
                                //Don't save the wp-posts for Woodmart theme
                                if (HMWP_Classes_Tools::isPluginActive('woocommerce/woocommerce.php') ) {
                                    if ($hmwp_text_mapping_from[$index] == 'wp-post-image' ) {
                                        continue;
                                    }
                                }

                                if ($hmwp_text_mapping_from[$index] <> $hmwp_text_mapping_to[$index] ) {
                                    $hmwp_text_mapping['from'][] = $hmwp_text_mapping_from[$index];
                                    $hmwp_text_mapping['to'][] = $hmwp_text_mapping_to[$index];
                                }
                            } else {
                                HMWP_Classes_Error::setError(esc_html__('Error: You entered the same text twice in the Text Mapping. We removed the duplicates to prevent any redirect errors.'));
                            }
                        }
                    }
                    HMWP_Classes_Tools::saveOptions('hmwp_text_mapping', json_encode($hmwp_text_mapping));

                }
            }

            //Save URL mapping
            if ($hmwp_url_mapping_from = HMWP_Classes_Tools::getValue('hmwp_url_mapping_from') ) {
                if ($hmwp_url_mapping_to = HMWP_Classes_Tools::getValue('hmwp_url_mapping_to') ) {
                    $hmwp_url_mapping = array();
                    foreach ( $hmwp_url_mapping_from as $index => $from ) {
                        if ($hmwp_url_mapping_from[$index] <> '' && $hmwp_url_mapping_to[$index] <> '' ) {
                            $hmwp_url_mapping_from[$index] = preg_replace('/[^A-Za-z0-9-_;:=%.#\/\?]/', '', $hmwp_url_mapping_from[$index]);
                            $hmwp_url_mapping_to[$index] = preg_replace('/[^A-Za-z0-9-_;:%=.#\/\?]/', '', $hmwp_url_mapping_to[$index]);

                            //if (substr_count($hmwp_url_mapping_from[$index], home_url()) == 1 && substr_count($hmwp_url_mapping_to[$index], home_url()) == 1) {
                            if (!isset($hmwp_url_mapping['from']) || (                                !in_array($hmwp_url_mapping_from[$index], (array)$hmwp_url_mapping['from']) 
                                && !in_array($hmwp_url_mapping_to[$index], (array)$hmwp_url_mapping['to'])) 
                            ) {
                                if ($hmwp_url_mapping_from[$index] <> $hmwp_url_mapping_to[$index] ) {
                                    $hmwp_url_mapping['from'][] = $hmwp_url_mapping_from[$index];
                                    $hmwp_url_mapping['to'][] = $hmwp_url_mapping_to[$index];
                                }
                            } else {
                                HMWP_Classes_Error::setError(esc_html__('Error: You entered the same URL twice in the URL Mapping. We removed the duplicates to prevent any redirect errors.'));
                            }
                        }
                    }


                    HMWP_Classes_Tools::saveOptions('hmwp_url_mapping', json_encode($hmwp_url_mapping));

                }

                if (!empty($hmwp_url_mapping) ) {
	                //show rules to be added manually
                    if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->clearRedirect()->setRewriteRules()->flushRewrites() ) {
	                    HMWP_Classes_Tools::saveOptions('test_frontend', false);
                        HMWP_Classes_Tools::saveOptions('error', true);
                    }
                }
            }

            //If no change is made on settings, just return
            if(!$this->model->checkOptionsChange()) {
                return;
            }

            if (HMWP_Classes_Tools::getOption('hmwp_file_cache') ) {
                //Flush the changes
                HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();
            }

            //Clear the cache if there are no errors
            if (!HMWP_Classes_Tools::getOption('error') ) {

                //Create the Wp-Rocket Burts Mapping for all blogs if not exists
                HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->rocket_burst_mapping();

                //Flush the changes
                HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();


                if (!HMWP_Classes_Tools::getOption('logout') ) {
                    HMWP_Classes_Tools::saveOptionsBackup();
                }

                HMWP_Classes_Tools::emptyCache();
                HMWP_Classes_Error::setError(esc_html__('Saved'), 'success');

                //Show the Nginx message to set up the config file
                if (HMWP_Classes_Tools::isNginx() && !HMWP_Classes_Tools::getOption('test_frontend') && HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default' ) {
                    $config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();
                    HMWP_Classes_Error::setError(sprintf(esc_html__("NGINX detected. In case you didn't add the code in the NGINX config already, please add the following line. %s", 'hide-my-wp'), '<br /><br /><code><strong>include ' . $config_file . ';</strong></code> <br /><br /><h5>' . esc_html__("Don't forget to reload the Nginx service.", 'hide-my-wp') . ' ' . '</h5><strong><br /><a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/how-to-setup-hide-my-wp-on-nginx-server/" target="_blank" style="color: red">' . esc_html__("Learn how to setup on Nginx server", 'hide-my-wp') . '</a></strong>'), 'notice', false);
                }

            }
            break;
        case 'hmwp_advsettings':

            if (!empty($_POST) ) {
                $this->model->saveValues($_POST);

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

                //Clear the cache if there are no errors
                if (!HMWP_Classes_Tools::getOption('error') ) {

                    if (!HMWP_Classes_Tools::getOption('logout') ) {
                        HMWP_Classes_Tools::saveOptionsBackup();
                    }

                    HMWP_Classes_Tools::emptyCache();
                    HMWP_Classes_Error::setError(esc_html__('Saved'), 'success');
                }

            }
            break;
        case 'hmwp_savecachepath':

            //Save the option to change the paths in the cache file
            HMWP_Classes_Tools::saveOptions('hmwp_change_in_cache', HMWP_Classes_Tools::getValue('hmwp_change_in_cache'));
            $json = array('success' => true, 'message' => esc_html__('Saved', 'hide-my-wp'));

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
                    $json = array('success' => false, 'message' => esc_html__('Path not allowed. Avoid paths like plugins and themes.', 'hide-my-wp'));
                }
            }else{
                HMWP_Classes_Tools::saveOptions('hmwp_change_in_cache_directory', '');
            }


            //If Ajax call, return saved
            if (HMWP_Classes_Tools::isAjax()) {
                HMWP_Classes_Tools::setHeader('json');
                echo json_encode($json);
                exit();
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
            HMWP_Classes_Tools::saveOptions('error', false);
            HMWP_Classes_Tools::saveOptions('logout', false);

            // Delete the restore transient
            delete_transient('hmwp_restore');

            //Clear the cache and remove the redirects
            HMWP_Classes_Tools::emptyCache();
            //Flush the WordPress rewrites
            HMWP_Classes_Tools::flushWPRewrites();

            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->clearRedirect();
            //Flush the changes
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();

            //Set the cookies for the current path
            $cookies = HMWP_Classes_ObjController::newInstance('HMWP_Models_Cookies');
            if (HMWP_Classes_Tools::isNginx() || $cookies->setCookiesCurrentPath() ) {

                remove_all_filters('wp_redirect');
                remove_all_filters('admin_url');
                wp_safe_redirect(HMWP_Classes_Tools::getSettingsUrl(HMWP_Classes_Tools::getValue('page')));
                exit();
            }

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

            //Clear the cache and remove the redirects
            HMWP_Classes_Tools::emptyCache();

            //Flush the WordPress rewrites
            HMWP_Classes_Tools::flushWPRewrites();

            //Flush the changes
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();

            if (!HMWP_Classes_Error::isError() ) {
                HMWP_Classes_Error::setError(esc_html__('The list of plugins and themes was updated with success!'), 'success');
            }
            break;
        case 'hmwp_confirm':
            HMWP_Classes_Tools::saveOptions('error', false);
            HMWP_Classes_Tools::saveOptions('logout', false);
            HMWP_Classes_Tools::saveOptions('test_frontend', false);

            //Send email notification about the path changed
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->sendEmail();

            //save to safe mode in case of db
            if (!HMWP_Classes_Tools::getOption('logout') ) {
                HMWP_Classes_Tools::saveOptionsBackup();
            }

            //Force the rechck security notification
            delete_option(HMWP_SECURITY_CHECK_TIME);

            HMWP_Classes_Tools::saveOptions('download_settings', true);

            break;
        case 'hmwp_manualrewrite':
            HMWP_Classes_Tools::saveOptions('error', false);
            HMWP_Classes_Tools::saveOptions('logout', false);
            HMWP_Classes_Tools::saveOptions('test_frontend', true);

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

            HMWP_Classes_Error::setError(esc_html__('Paths changed in the existing cache files', 'hide-my-wp'), 'success');
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
            } else {
                echo json_encode(HMWP_Classes_Tools::$options);
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

            HMWP_Classes_Error::setError(esc_html__('Great! The initial values are restored.', 'hide-my-wp') . " <br /> ", 'success');

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
                                HMWP_Classes_Tools::$options[$key] = $value;
                            }
                        }
                        HMWP_Classes_Tools::saveOptions();
                        HMWP_Classes_Error::setError(esc_html__('Great! The backup is restored.', 'hide-my-wp') . " <br /> ", 'success');

                        if (!HMWP_Classes_Tools::getOption('error') ) {
                            HMWP_Classes_Tools::emptyCache();
                            //Flush the WordPress rewrites
                            add_action(
                                'admin_footer', array(
                                'HMWP_Classes_Tools',
                                'flushWPRewrites'
                                ), PHP_INT_MAX 
                            );
                        }

                        if (!HMWP_Classes_Tools::getOption('error') && !HMWP_Classes_Tools::getOption('logout') ) {
                            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();
                        }

                    } elseif (is_array($options) && isset($options['hmw_ver']) ) {
                        foreach ( $options as $key => $value ) {
                            if ($key <> 'hmw_token' ) {
                                HMWP_Classes_Tools::$options[str_replace('hmwp_', 'hmwp_', $key)] = $value;
                            }
                        }
                        HMWP_Classes_Tools::saveOptions();
                        HMWP_Classes_Error::setError(esc_html__('Great! The backup is restored.', 'hide-my-wp') . " <br /> ", 'success');

                        if (!HMWP_Classes_Tools::getOption('error') ) {
                            HMWP_Classes_Tools::emptyCache();
                            //Flush the WordPress rewrites
                            add_action(
                                'admin_footer', array(
                                'HMWP_Classes_Tools',
                                'flushWPRewrites'
                                ), PHP_INT_MAX 
                            );
                        }

                        if (!HMWP_Classes_Tools::getOption('error') && !HMWP_Classes_Tools::getOption('logout') ) {
                            //Clear the cache and remove the redirects
                            HMWP_Classes_Tools::emptyCache();
                            //Flush the WordPress rewrites
                            HMWP_Classes_Tools::flushWPRewrites();

                            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->clearRedirect();
                            //Flush the changes
                            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();
                        }

                    } else {
                        HMWP_Classes_Error::setError(esc_html__('Error! The backup is not valid.', 'hide-my-wp') . " <br /> ");
                    }
                } catch ( Exception $e ) {
                    HMWP_Classes_Error::setError(esc_html__('Error! The backup is not valid.', 'hide-my-wp') . " <br /> ");
                }
            } else {
                HMWP_Classes_Error::setError(esc_html__('Error! You have to enter a previous saved backup file.', 'hide-my-wp') . " <br /> ");
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
     * If javascript is not loaded
     * @return void
     */
    public function hookFooter()
    {
        echo '<noscript><style>.tab-panel {display: block;}</style></noscript>';
    }

}
