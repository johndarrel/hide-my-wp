<?php
/**
 * Settings Model
 * Handles the plugin settings actions and database
 *
 * @file  The Settings Model file
 * @package HMWP/SettingsModel
 * @since 4.0.0
 */
defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Settings
{

    /**
     * Set the permalinks in database
     *
     * @param  array
     *  $params
     * @throws Exception
     */
    public function savePermalinks($params)
    {
        HMWP_Classes_Tools::saveOptions('error', false);
        HMWP_Classes_Tools::saveOptions('changes', false);

        if ($params['hmwp_admin_url'] == $params['hmwp_login_url'] && $params['hmwp_admin_url'] <> '') {
	        HMWP_Classes_Tools::saveOptions('test_frontend', false);
	        HMWP_Classes_Tools::saveOptions('error', true);
            HMWP_Classes_Error::setError(esc_html__("You can't set both ADMIN and LOGIN with the same name. Please use different names", 'hide-my-wp'));
            return;
        }

        //send email when the admin is changed
        if (isset($params['hmwp_send_email'])) {
            HMWP_Classes_Tools::$default['hmwp_send_email'] = $params['hmwp_send_email'];
        }

        if ($params['hmwp_mode'] == 'default') {
            $params = HMWP_Classes_Tools::$default;
        }

        ////////////////////////////////////////////
        //Set the Category and Tags dirs
        global $wp_rewrite;
        $blog_prefix = '';
        if (HMWP_Classes_Tools::isMultisites() && !is_subdomain_install() && is_main_site() && 0 === strpos(get_option('permalink_structure'), '/blog/')) {
            $blog_prefix = '/blog';
        }

        if (isset($params['hmwp_category_base']) && method_exists($wp_rewrite, 'set_category_base')) {
            $category_base = $params['hmwp_category_base'];
            if (!empty($category_base)) {
                $category_base = $blog_prefix . preg_replace('#/+#', '/', '/' . str_replace('#', '', $category_base));
            }
            $wp_rewrite->set_category_base($category_base);
        }

        if (isset($params['hmwp_tag_base']) && method_exists($wp_rewrite, 'set_tag_base')) {
            $tag_base = $params['hmwp_tag_base'];
            if (!empty($tag_base)) {
                $tag_base = $blog_prefix . preg_replace('#/+#', '/', '/' . str_replace('#', '', $tag_base));
            }
            $wp_rewrite->set_tag_base($tag_base);
        }
        ////////////////////////////////////////////

        //If the admin is changed, require a logout if necessary
        $lastsafeoptions = HMWP_Classes_Tools::getOptions(true);
        if(!empty($lastsafeoptions)) {
            if ($lastsafeoptions['hmwp_admin_url'] <> $params['hmwp_admin_url']) {
                HMWP_Classes_Tools::saveOptions('logout', true);
            } elseif ($lastsafeoptions['hmwp_login_url'] <> $params['hmwp_login_url']) {
                HMWP_Classes_Tools::saveOptions('logout', true);
            } elseif ($lastsafeoptions['hmwp_admin-ajax_url'] <> $params['hmwp_admin-ajax_url']) {
                HMWP_Classes_Tools::saveOptions('logout', true);
            } elseif ($lastsafeoptions['hmwp_wp-json'] <> $params['hmwp_wp-json']) {
                HMWP_Classes_Tools::saveOptions('logout', true);
            } elseif ($lastsafeoptions['hmwp_upload_url'] <> $params['hmwp_upload_url']) {
                HMWP_Classes_Tools::saveOptions('logout', true);
            } elseif ($lastsafeoptions['hmwp_wp-content_url'] <> $params['hmwp_wp-content_url']) {
                HMWP_Classes_Tools::saveOptions('logout', true);
            }

        }

        //Save all values
        $this->saveValues($params, true);

        //Some values need to be saved as blank is case no data is received
        //Set them to blank or value
        HMWP_Classes_Tools::saveOptions('hmwp_lostpassword_url', HMWP_Classes_Tools::getValue('hmwp_lostpassword_url', ''));
        HMWP_Classes_Tools::saveOptions('hmwp_register_url', HMWP_Classes_Tools::getValue('hmwp_register_url', ''));
        HMWP_Classes_Tools::saveOptions('hmwp_logout_url', HMWP_Classes_Tools::getValue('hmwp_logout_url', ''));

        //Make sure the theme style name is ending with .css to be a static file
        if($stylename = HMWP_Classes_Tools::getValue('hmwp_themes_style')) {
            if(strpos($stylename, '.css') === false) {
                HMWP_Classes_Tools::saveOptions('hmwp_themes_style', $stylename . '.css');
            }
        }

        //generate unique names for plugins if needed
        if (HMWP_Classes_Tools::getOption('hmwp_hide_plugins')) {
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->hidePluginNames();
        }
        if (HMWP_Classes_Tools::getOption('hmwp_hide_themes')) {
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->hideThemeNames();
        }

        if(!HMWP_Classes_Tools::getOption('hmwp_hide_oldpaths')) {
            HMWP_Classes_Tools::saveOptions('hmwp_hide_oldpaths_plugins', 0);
            HMWP_Classes_Tools::saveOptions('hmwp_hide_oldpaths_themes', 0);
        }

        //If no change is made on settings, just return
        if(!$this->checkOptionsChange()) {
            return;
        }

        //Save the rules and add the rewrites
        $this->saveRules();

        //check if the config file is writable or is WP-engine server
        if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->isConfigWritable() || HMWP_Classes_Tools::isWpengine()) {
            //if not writeable, call the rules to show manually changes
	        //show rules to be added manually
            if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->clearRedirect()->setRewriteRules()->flushRewrites() ) {
	            HMWP_Classes_Tools::saveOptions('test_frontend', false);
	            HMWP_Classes_Tools::saveOptions('error', true);
            }
        }


    }

    /**
     * Check if the current setup changed the last settings
     *
     * @return bool
     */
    public function checkOptionsChange()
    {
        $lastsafeoptions = HMWP_Classes_Tools::getOptions(true);

        foreach ($lastsafeoptions as $index => $value){
            if(HMWP_Classes_Tools::getOption($index) <> $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save the Values in database
     *
     * @param $params
     * @param bool $validate
     */
    public function saveValues($params, $validate = false)
    {
        //Save the option values
        foreach ($params as $key => $value) {
            if (in_array($key, array_keys(HMWP_Classes_Tools::$options))) {
                //Make sure is set in POST
                if (HMWP_Classes_Tools::getIsset($key)) {
                    //sanitize the value first
                    $value = HMWP_Classes_Tools::getValue($key);

                    //set the default value in case of nothing to prevent empty paths and errors
                    if ($value == '') {
                        if (isset(HMWP_Classes_Tools::$default[$key])) {
                            $value = HMWP_Classes_Tools::$default[$key];
                        } elseif (isset(HMWP_Classes_Tools::$init[$key])) {
                            $value = HMWP_Classes_Tools::$init[$key];
                        }
                    }

                    //Detect Invalid Names
                    if ($validate) {
                        if (isset($params['hmwp_mode']) && $params['hmwp_mode'] <> 'default') {
                            if (!$this->invalidName($key, $value)) { //if the name is valid
                                //Detect Weak Names
                                $this->weakName($value); //show weak names
                                HMWP_Classes_Tools::saveOptions($key, $value);
                            }
                        } else {
                            HMWP_Classes_Tools::saveOptions($key, $value);
                        }
                    } else {
                        HMWP_Classes_Tools::saveOptions($key, $value);
                    }
                }
            }
        }
    }

    /**
     * Save the rules in the config file
     *
     * @throws Exception
     */
    public function saveRules()
    {
        //CLEAR RULES ON DEFAULT
        if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default') {
            HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeToFile('', 'HMWP_VULNERABILITY');
            return;
        }


        //INSERT SEURITY RULES
        if (!HMWP_Classes_Tools::isIIS()) {
            //For Nginx and Apache the rules can be inserted separately
            $rules = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getInjectionRewrite();

            if(strlen($rules) > 1) {
                if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeToFile($rules, 'HMWP_VULNERABILITY') ) {
                    $config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();
                    HMWP_Classes_Error::setError(sprintf(esc_html__('Config file is not writable. Create the file if not exists or copy to %s file with the following lines: %s', 'hide-my-wp'), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong># BEGIN HMWP_VULNERABILITY<br />' . htmlentities(str_replace('    ', ' ', $rules)) . '# END HMWP_VULNERABILITY</strong></pre>'));
                }
            }
        }

    }

    /**
     * Check invalid name and avoid errors
     *
     * @param  string $key  DB Option name
     * @param  string $name Option value
     * @return bool
     */
    public function invalidName($key, $name)
    {
        if(!is_string($name)) {
            return false;
        }

        $invalid_paths = array(
            'index.php',
            'readme.html',
            'sitemap.xml',
            '.htaccess',
            'license.txt',
            'wp-blog-header.php',
            'wp-config.php',
            'wp-config-sample.php',
            'wp-activate.php',
            'wp-cron.php',
            'wp-mail.php',
            'wp-load.php',
            'wp-links-opml.php',
            'wp-settings.php',
            'wp-signup.php',
            'wp-trackback.php',
            'xmlrpc.php',
            'content',
            'includes',
            'css',
            'js',
            'font',
        );

        if(($key <> 'hmwp_themes_url' && $name == 'themes') || ($key == 'hmwp_themes_url' && $name == 'assets') ||  ($key <> 'hmwp_upload_url' && $name == 'uploads')) {
            HMWP_Classes_Error::setError(sprintf(esc_html__("Invalid name detected: %s. Add only the final path name to avoid WordPress errors.", 'hide-my-wp'), '<strong>' . $name . '</strong>'));
            return true;
        }

        if (strlen($name) > 1 && strlen($name) < 3) {
            HMWP_Classes_Error::setError(sprintf(esc_html__("Short name detected: %s. You need to use unique paths with more than 4 chars to avoid WordPress errors.", 'hide-my-wp'), '<strong>' . $name . '</strong>'));
            return true;
        }
        if (in_array($name, $invalid_paths)) {
            HMWP_Classes_Error::setError(sprintf(esc_html__("Invalid name detected: %s. You need to use another name to avoid WordPress errors.", 'hide-my-wp'), '<strong>' . $name . '</strong>'));
            return true;
        }

        if (strpos($name, '//') !== false) {
            HMWP_Classes_Error::setError(sprintf(esc_html__("Invalid name detected: %s. Add only the final path name to avoid WordPress errors.", 'hide-my-wp'), '<strong>' . $name . '</strong>'));
            return true;
        }
        if (strpos($name, '/') !== false && strpos($name, '/') == 0) {
            HMWP_Classes_Error::setError(sprintf(esc_html__("Invalid name detected: %s. The name can't start with / to avoid WordPress errors.", 'hide-my-wp'), '<strong>' . $name . '</strong>'));
            return true;
        }
        if (strpos($name, '/') !== false && substr($name, -1) == '/') {
            HMWP_Classes_Error::setError(sprintf(esc_html__("Invalid name detected: %s. The name can't end with / to avoid WordPress errors.", 'hide-my-wp'), '<strong>' . $name . '</strong>'));
            return true;
        }
        $array = explode('/', $name);
        if (!empty($array)) {
            foreach ($array as $row) {
                if (substr($row, -1) == '.') {
                    HMWP_Classes_Error::setError(sprintf(esc_html__("Invalid name detected: %s. The paths can't end with . to avoid WordPress errors.", 'hide-my-wp'), '<strong>' . $name . '</strong>'));
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if the name is week for security
     *
     * @param $name
     */
    public function weakName($name)
    {
        $invalit_paths = array(
            'login',
            'mylogin',
            'wp-login',
            'admin',
            'wp-mail.php',
            'wp-settings.php',
            'wp-signup.php',
            'wp-trackback.php',
            'xmlrpc.php',
            'wp-include',
        );

        if (in_array($name, $invalit_paths)) {
            HMWP_Classes_Error::setError(sprintf(esc_html__("Weak name detected: %s. You need to use another name to increase your website security.", 'hide-my-wp'), '<strong>' . $name . '</strong>'));
        }
    }
}
