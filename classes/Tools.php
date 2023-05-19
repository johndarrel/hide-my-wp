<?php
/**
 * Handles the parameters and URLs
 *
 * @file The Tools file
 * @package HMWP/Tools
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Classes_Tools
{

    /**
     *
     *
     * @var array Saved options in database
     */
    public static $init = array(), $default = array(), $lite = array();
    public static $options = array();
    public static $debug = array();
    public static $active_plugins;

    /**
     *
     *
     * @var integer Count the errors in site
     */
    static $errors_count = 0;

    static $current_user_role = 'default';

    /**
     * HMWP_Classes_Tools constructor.
     */
    public function __construct()
    {

        /////////////////////////////////////////////////////////////
        //Check the memory and make sure it's enough
        //Check the max memory usage
        $maxmemory = self::getMaxMemory();
        if ($maxmemory && $maxmemory < 60 ) {
            if (defined('WP_MAX_MEMORY_LIMIT') && (int)WP_MAX_MEMORY_LIMIT > 60 ) {
                @ini_set('memory_limit', apply_filters('admin_memory_limit', WP_MAX_MEMORY_LIMIT));
            } else {
                define('HMWP_DISABLE', true);
                HMWP_Classes_Error::setError(sprintf(esc_html__('Your memory limit is %sM. You need at least %sM to prevent loading errors in frontend. See: %sIncreasing memory allocated to PHP%s', 'hide-my-wp'), $maxmemory, 64, '<a href="https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">', '</a>'));
            }
        }
        ////////////////////////////////////////////////////

        //Get the plugin options from database
        self::$options = self::getOptions();

        //Load multilanguage
        add_action("init", array($this, 'loadMultilanguage'));

        //If it's admin panel
        if(is_admin() || is_network_admin()) {
            //Check the Plugin database update
            self::updateDatabase();

            //add setting link in plugin
            add_filter('plugin_action_links_' . HMWP_BASENAME, array($this, 'hookActionlink'));
            add_filter('network_admin_plugin_action_links_' . HMWP_BASENAME, array($this, 'hookActionlink'));

            //check plugin license
            add_action('request_metadata_http_result', array($this, 'checkLicenseOnUpdate'));


        }

    }

    /**
     * Check the memory and make sure it's enough
     *
     * @return bool|string
     */
    public static function getMaxMemory()
    {
        try {
            $memory_limit = @ini_get('memory_limit');
            if ((int)$memory_limit > 0 ) {
                if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches) ) {
                    if ($matches[2] == 'G' ) {
                        $memory_limit = $matches[1] * 1024 * 1024 * 1024; // nnnM -> nnn MB
                    } elseif ($matches[2] == 'M' ) {
                        $memory_limit = $matches[1] * 1024 * 1024; // nnnM -> nnn MB
                    } else if ($matches[2] == 'K' ) {
                        $memory_limit = $matches[1] * 1024; // nnnK -> nnn KB
                    }
                }
                if ((int)$memory_limit > 0 ) {
                    return number_format((int)$memory_limit / 1024 / 1024, 0, '', '');
                }
            }
        } catch ( Exception $e ) {
        }

        return false;

    }

    /**
     * Load the Options from user option table in DB
     *
     * @param bool|false $safe
     *
     * @return array
     */
    public static function getOptions( $safe = false )
    {
        $keymeta = HMWP_OPTION;

	    $homepath = '';
		if(parse_url(site_url(), PHP_URL_PATH)){
			$homepath = ltrim(parse_url(site_url(), PHP_URL_PATH), '/');
		}

	    $pluginurl = ltrim(parse_url(plugins_url(), PHP_URL_PATH), '/');
	    $contenturl = ltrim(parse_url(content_url(), PHP_URL_PATH), '/');

        $plugin_relative_url = trim(preg_replace('/' . str_replace('/', '\/', $homepath) . '/', '', $pluginurl, 1), '/');
        $content_relative_url = trim(preg_replace('/' . str_replace('/', '\/', $homepath) . '/', '', $contenturl, 1), '/');

        if ($safe ) {
            $keymeta = HMWP_OPTION_SAFE;
        }

        self::$init = array(
            'hmwp_ver' => 0,
            //--
            'api_token' => false,
            'hmwp_token' => false,
            //--
            'hmwp_valid' => 1,
            'hmwp_expires' => 0,
            'hmwp_disable' => HMWP_Classes_Tools::generateRandomString(16),
            'hmwp_disable_name' => HMWP_Classes_Tools::generateRandomString(16),
            //--
            'hmwp_plugin_name' => _HMWP_PLUGIN_FULL_NAME_,
            'hmwp_plugin_menu' => str_replace(' Ghost', '', _HMWP_PLUGIN_FULL_NAME_) ,
            'hmwp_plugin_logo' =>  false ,
            'hmwp_plugin_icon' => 'dashicons-shield-alt',
            'hmwp_plugin_website' => 'https://hidemywpghost.com',
            'hmwp_plugin_account_show' => 1,
            //--
            'logout' => 0,
            'error' => 0,
            'file_mappings' => array(),
            'test_frontend' => 0,
            'changes' => 0,
            'admin_notice' => array(),
			'prevent_slow_loading' => 1,
            'hmwp_rewrites_in_wp_rules' => 0,
            'hmwp_server_type' => 'auto',
	        //--
            'hmwp_loading_hook' => array('normal'), //load when the other plugins are initialized
            'hmwp_firstload' => 0, //load the plugin as Must Use Plugin
            'hmwp_priorityload' => 0, //load the plugin on plugin start
            'hmwp_laterload' => 0, //load the plugin on template redirect
            //--
            'hmwp_fix_relative' => 0,
            'hmwp_remove_third_hooks' => 0,
            'hmwp_send_email' => 0,
            'hmwp_activity_log' => 0,
            'hmwp_activity_log_roles' => array(),
            'hmwp_email_address' => '',

            //-- Brute Force
            'hmwp_bruteforce' => 0,
            'hmwp_bruteforce_register' => 0,
            'hmwp_bruteforce_lostpassword' => 0,
            'hmwp_brute_message' => esc_html__('Your IP has been flagged for potential security violations. Please try again in a little while...', 'hide-my-wp'),
            'whitelist_ip' => array(),
            'banlist_ip' => array(),
            'hmwp_hide_classes' => json_encode(array()),
            'trusted_ip_header' => '',
            'whitelist_paths' => 0,

            //Math reCaptcha
            'brute_use_math' => 1,
            'brute_max_attempts' => 5,
            'brute_max_timeout' => 3600,
            //reCaptcha V2
            'brute_use_captcha' => 0,
            'brute_captcha_site_key' => '',
            'brute_captcha_secret_key' => '',
            'brute_captcha_theme' => 'light',
            'brute_captcha_language' => '',
            //reCaptcha V2
            'brute_use_captcha_v3' => 0,
            'brute_captcha_site_key_v3' => '',
            'brute_captcha_secret_key_v3' => '',

            //tweaks
            'hmwp_hide_admin_toolbar' => 0,
            'hmwp_hide_admin_toolbar_roles' => array('customer','subscriber'),
            //--
            'hmwp_change_in_cache' => ((defined('WP_CACHE') && WP_CACHE) ? 1 : 0),
            'hmwp_change_in_cache_directory' => '',
            'hmwp_hide_loggedusers' => 1,
            'hmwp_hide_version' => 1,
            'hmwp_hide_generator' => 1,
            'hmwp_hide_prefetch' => 1,
            'hmwp_hide_comments' => 1,
            'hmwp_hide_wp_text' => 0,

            'hmwp_hide_feed' => 0,
            'hmwp_hide_in_feed' => 0,
            'hmwp_hide_in_sitemap' => 0,
            'hmwp_hide_author_in_sitemap' => 1,
            'hmwp_robots' => 0,

            'hmwp_disable_emojicons' => 0,
            'hmwp_disable_manifest' => 1,
            'hmwp_disable_embeds' => 0,
            'hmwp_disable_debug' => 1,
            //--
            'hmwp_disable_click' => 0,
            'hmwp_disable_click_loggedusers' => 0,
            'hmwp_disable_click_roles' => array('subscriber'),
            'hmwp_disable_click_message' => "Right click is disabled!",

            'hmwp_disable_inspect' => 0,
            'hmwp_disable_inspect_blank' => 0,
            'hmwp_disable_inspect_loggedusers' => 0,
            'hmwp_disable_inspect_roles' => array('subscriber'),
            'hmwp_disable_inspect_message' => "Inspect Element is disabled!",

            'hmwp_disable_source' => 0,
            'hmwp_disable_source_loggedusers' => 0,
            'hmwp_disable_source_roles' => array('subscriber'),
            'hmwp_disable_source_message' => "View Source is disabled!",

            'hmwp_disable_copy_paste' => 0,
            'hmwp_disable_copy_paste_loggedusers' => 0,
            'hmwp_disable_copy_paste_roles' => array('subscriber'),
            'hmwp_disable_copy_paste_message' => "Copy/Paste is disabled!",

            'hmwp_disable_drag_drop' => 0,
            'hmwp_disable_drag_drop_loggedusers' => 0,
            'hmwp_disable_drag_drop_roles' => array('subscriber'),
            'hmwp_disable_drag_drop_message' => "Drag-n-Drop is disabled!",

            'hmwp_disable_recording' => 0,
            'hmwp_disable_recording_loggedusers' => 0,
            'hmwp_disable_recording_roles' => array('subscriber'),
            'hmwp_disable_recording_message' => "Screen Recording is disabled!",
            //--
            'hmwp_disable_screen_capture' => 0,
            'hmwp_file_cache' => 0,
            'hmwp_url_mapping' => json_encode(array()),
            'hmwp_mapping_classes' => 1,
            'hmwp_mapping_file' => 0,
            'hmwp_text_mapping' => json_encode(
                array(
                    'from' => array('wp-caption'),
                    'to' => array('caption'),
                )
            ),
            'hmwp_cdn_urls' => json_encode(array()),
            'hmwp_security_alert' => 1,
            //--
            'hmwp_hide_plugins_advanced' => 0,
            'hmwp_hide_themes_advanced' => 0,
            //--

            //redirects
            'hmwp_url_redirect' => '.',
            'hmwp_do_redirects' => 0,
            'hmwp_logged_users_redirect' => 0,
            'hmwp_url_redirects' => array('default' => array('login' => '', 'logout' => '')),
            'hmwp_signup_template' => 0,

            'hmwp_mapping_text_show' => 1,
            'hmwp_mapping_url_show' => 1,
            'hmwp_mapping_cdn_show' => 1,
	        //PRO
            'hmwp_bruteforce_woocommerce' => 0,

        );
        self::$default = array(
            'hmwp_mode' => 'default',
            'hmwp_admin_url' => 'wp-admin',
            'hmwp_login_url' => 'wp-login.php',
            'hmwp_activate_url' => 'wp-activate.php',
            'hmwp_lostpassword_url' => '',
            'hmwp_register_url' => '',
            'hmwp_logout_url' => '',

            'hmwp_plugin_url' => $plugin_relative_url,
            'hmwp_plugins' => array(),
            'hmwp_themes_url' => 'themes',
            'hmwp_themes' => array(),
            'hmwp_upload_url' => 'uploads',
            'hmwp_admin-ajax_url' => 'admin-ajax.php',
            'hmwp_wp-signup_url' => 'wp-signup.php',
            'hmwp_hideajax_paths' => 0,
            'hmwp_hideajax_admin' => 0,
            'hmwp_tags_url' => 'tag',
            'hmwp_wp-content_url' => $content_relative_url,
            'hmwp_wp-includes_url' => 'wp-includes',
            'hmwp_author_url' => 'author',
            'hmwp_hide_authors' => 0,
            'hmwp_wp-comments-post' => 'wp-comments-post.php',
            'hmwp_themes_style' => 'style.css',
            'hmwp_hide_img_classes' => 0,
            'hmwp_hide_styleids' => 0,
            'hmwp_noncekey' => '_wpnonce',
            'hmwp_wp-json' => 'wp-json',
            'hmwp_hide_rest_api' => 0,
            'hmwp_disable_rest_api' => 0,
            'hmwp_disable_xmlrpc' => 0,
            'hmwp_hide_rsd' => 0,
            'hmwp_hide_admin' => 0,
            'hmwp_hide_newadmin' => 0,
            'hmwp_hide_admin_loggedusers' => 0,
            'hmwp_hide_login' => 0,
            'hmwp_hide_wplogin' => 0,
            'hmwp_disable_language_switcher' => 0,
            'hmwp_hide_plugins' => 0,
            'hmwp_hide_all_plugins' => 0,
            'hmwp_hide_themes' => 0,
            'hmwp_emulate_cms' => '',

            //--secure headers
            'hmwp_sqlinjection' => 0,
            'hmwp_sqlinjection_level' => 1,
            'hmwp_security_header' => 0,
            'hmwp_hide_unsafe_headers' => 0,
            'hmwp_security_headers' => array(
                "Strict-Transport-Security" => "max-age=15768000;includeSubdomains",
                "Content-Security-Policy" => "object-src 'none'",
                "X-XSS-Protection" => "1; mode=block",
                "X-Content-Type-Options" => "nosniff",
            ),
            //--
            'hmwp_detectors_block' => 0,
            'hmwp_hide_commonfiles' => 0,
            'hmwp_disable_browsing' => 0,
            'hmwp_hide_oldpaths' => 0,
            'hmwp_hide_oldpaths_plugins' => 0,
            'hmwp_hide_oldpaths_themes' => 0,
            'hmwp_hide_oldpaths_types' => array('css', 'js', 'php', 'txt', 'html'),
            'hmwp_hide_commonfiles_files' => array('wp-config-sample.php', 'readme.html', 'readme.txt', 'install.php', 'license.txt', 'php.ini', 'upgrade.php', 'bb-config.php', 'error_log'),
            //
            'hmwp_category_base' => '',
            'hmwp_tag_base' => '',
            //
        );
        self::$lite = array(
            'hmwp_mode' => 'lite',
            'hmwp_login_url' => 'newlogin',
            'hmwp_activate_url' => 'activate',
            'hmwp_lostpassword_url' => 'lostpass',
            'hmwp_register_url' => 'register',
            'hmwp_logout_url' => '',
            'hmwp_admin-ajax_url' => 'admin-ajax.php',
            'hmwp_hideajax_admin' => 0,
            'hmwp_hideajax_paths' => 0,
            'hmwp_plugin_url' => 'core/modules',
            'hmwp_themes_url' => 'core/views',
            'hmwp_upload_url' => 'storage',
            'hmwp_wp-content_url' => 'core',
            'hmwp_wp-includes_url' => 'lib',
            'hmwp_author_url' => 'writer',
            'hmwp_wp-comments-post' => 'comments',
            'hmwp_themes_style' => 'design.css',
            'hmwp_wp-json' => 'wp-json',
            'hmwp_hide_admin' => 1,
            'hmwp_hide_newadmin' => 0,
            'hmwp_hide_admin_loggedusers' => 0,
            'hmwp_hide_login' => 1,
            'hmwp_hide_wplogin' => 1,
            'hmwp_disable_language_switcher' => 0,
            'hmwp_hide_plugins' => 1,
            'hmwp_hide_all_plugins' => 0,
            'hmwp_hide_themes' => 1,
            'hmwp_emulate_cms' => 'drupal',
            //
            'hmwp_hide_img_classes' => 1,
            'hmwp_hide_rest_api' => 1,
            'hmwp_disable_rest_api' => 0,
            'hmwp_disable_xmlrpc' => 0,
            'hmwp_hide_rsd' => 1,
            //
            'hmwp_sqlinjection' => 0,
            'hmwp_security_header' => 1,
            'hmwp_hide_unsafe_headers' => 1,

            //PRO
            'hmwp_detectors_block' => 0,
            'hmwp_hide_styleids' => 0,
            'hmwp_hide_authors' => 0,
            'hmwp_disable_browsing' => 0,
            'hmwp_hide_commonfiles' => 0,
            'hmwp_hide_oldpaths' => 0,
            'hmwp_hide_oldpaths_plugins' => 0,
            'hmwp_hide_oldpaths_themes' => 0,
        );

        if (self::isMultisites() && defined('BLOG_ID_CURRENT_SITE') ) {
            $options = json_decode(get_blog_option(BLOG_ID_CURRENT_SITE, $keymeta), true);
        } else {
            $options = json_decode(get_option($keymeta), true);
        }

        //make sure it works with WP Client plugin by default
        if (self::isPluginActive('wp-client/wp-client.php') ) {
            self::$lite['hmwp_wp-content_url'] = 'include';
        }

        //merge the option
        if (is_array($options) ) {
            $options = @array_merge(self::$init, self::$default, $options);
        } else {
            $options = @array_merge(self::$init, self::$default);
        }

        //validate custom cache directory
        if(isset($options['hmwp_change_in_cache_directory']) && $options['hmwp_change_in_cache_directory'] <> '') {
            if(strpos($options['hmwp_change_in_cache_directory'], 'wp-content') !== false) {
                $options['hmwp_change_in_cache_directory'] = '';
            }
        }

        //Set the categories and tags paths
        $category_base = get_option('category_base');
        $tag_base = get_option('tag_base');

        if (self::isMultisites() && !is_subdomain_install() && is_main_site() && 0 === strpos(get_option('permalink_structure'), '/blog/') ) {
            $category_base = preg_replace('|^/?blog|', '', $category_base);
            $tag_base = preg_replace('|^/?blog|', '', $tag_base);
        }

        $options['hmwp_category_base'] = $category_base;
        $options['hmwp_tag_base'] = $tag_base;

	    if(HMW_PRIORITY) $options['hmwp_priorityload'] = 1;
	    if(HMW_RULES_IN_WP_RULES) $options['hmwp_rewrites_in_wp_rules'] = 1;

	    return $options;
    }

    /**
     * Update the plugin database with the last changed
     */
    private static function updateDatabase()
    {
        //On plugin update
        if(self::$options['hmwp_ver'] < HMWP_VERSION_ID ) {

            //Upgrade from Lite Version
            if (get_option('hmw_options') ) {
                $options = json_decode(get_option('hmw_options'), true);
                if (!empty($options) ) {
                    foreach ( $options as $key => $value ) {
                        self::$options[str_replace('hmw_', 'hmwp_', $key)] = $value;
                    }
                }
                delete_option('hmw_options');
            }

            //Set default hmwp_hide_wplogin
            if (!isset(self::$options['hmwp_hide_wplogin']) && isset(self::$options['hmwp_hide_login']) && self::$options['hmwp_hide_login'] ) {
                self::$options['hmwp_hide_wplogin'] = self::$options['hmwp_hide_login'];
            }

            //Initialize the account show option
            if (!isset(self::$options['hmwp_plugin_account_show']) ) {
                self::$options['hmwp_plugin_account_show'] = 1;
            }

            //upgrade the redirects to the new redirects
            if (isset(self::$options['hmwp_logout_redirect']) && self::$options['hmwp_logout_redirect']) {
                self::$options['hmwp_url_redirects']['default']['logout'] = self::$options['hmwp_logout_redirect'];
                unset(self::$options['hmwp_logout_redirect']);
            }

            if (isset(self::$options['hmwp_in_dashboard']) && self::$options['hmwp_in_dashboard']) {
                self::$options['hmwp_hide_admin_toolbar'] = self::$options['hmwp_in_dashboard'];
                unset(self::$options['hmwp_in_dashboard']);
            }

            if (isset(self::$options['hmwp_shutdownload']) && self::$options['hmwp_shutdownload']) {
                self::$options['hmwp_hide_in_sitemap'] = self::$options['hmwp_shutdownload'];
                unset(self::$options['hmwp_shutdownload']);
            }

            self::$options['hmwp_ver'] = HMWP_VERSION_ID;
            self::saveOptions();
        }
    }

	/**
	 * Get the default value
	 *
	 * @since 5.0.19
	 *
	 * @param $key
	 * @return false|mixed
	 */
	public static function getDefault( $key )
	{
		if (isset(self::$default[$key]) ) {
			return self::$default[$key];
		}

		return false;

	}

    /**
     * Get the option from database
     *
     * @param $key
     *
     * @return mixed
     */
    public static function getOption( $key )
    {
        if (!isset(self::$options[$key]) ) {
            self::$options = self::getOptions();

            if (!isset(self::$options[$key]) ) {
                self::$options[$key] = 0;
            }
        }

        return apply_filters('hmwp_option_' . $key, self::$options[$key]);
    }

    /**
     * Save the Options in user option table in DB
     *
     * @param string     $key
     * @param string     $value
     * @param bool|false $safe
     */
    public static function saveOptions( $key = null, $value = '', $safe = false )
    {
        $keymeta = HMWP_OPTION;

        if ($safe ) {
            $keymeta = HMWP_OPTION_SAFE;
        }

        if (isset($key) ) {
            self::$options[$key] = $value;
        }

        if (self::isMultisites() && defined('BLOG_ID_CURRENT_SITE') ) {
            update_blog_option(BLOG_ID_CURRENT_SITE, $keymeta, json_encode(self::$options));
        } else {
            update_option($keymeta, json_encode(self::$options));
        }
    }

    /**
     * Save the options into backup
     */
    public static function saveOptionsBackup()
    {
        //Save the working options into backup
        foreach ( self::$options as $key => $value ) {
            HMWP_Classes_Tools::saveOptions($key, $value, true);
        }
    }

    /**
     * Add a link to settings in the plugin list
     *
     * @param array  $links
     *
     * @return array
     */
    public function hookActionlink( $links )
    {
        $links[] = '<a href="' . self::getSettingsUrl() . '">' . esc_html__('Settings', 'hide-my-wp') . '</a>';
        $links[] = '<a href="https://hidemywpghost.com/hide-my-wp-pricing/" target="_blank" style="font-weight: bold;color: #007cba">' . esc_html__('Go PRO', 'hide-my-wp') . '</a>';
        return array_reverse($links);
    }


    /**
     * Load the multilanguage support from .mo
     */
    public static function loadMultilanguage()
    {
        if (!defined('WP_PLUGIN_DIR') ) {
            load_plugin_textdomain(dirname(HMWP_BASENAME), dirname(HMWP_BASENAME) . '/languages/');
        } else {
            load_plugin_textdomain(dirname(HMWP_BASENAME), null, dirname(HMWP_BASENAME) . '/languages/');
        }
    }

    /**
     * Check if it's Ajax call
     *
     * @return bool
     */
    public static function isAjax()
    {
        if (defined('DOING_AJAX') && DOING_AJAX ) {
            return true;
        }

        return false;
    }

    /**
     * Check if it's valid for changing the paths
     * Change the paths in admin, logged users or visitors
     *
     * @return bool
     */
    public static function doChangePaths()
    {

        //If allways change paths admin & frontend
        if (HMW_ALWAYS_CHANGE_PATHS ) {
            return true;
        }

        //If not admin
        if ((!is_admin() && !is_network_admin()) || HMWP_Classes_Tools::isAjax() ) {

            //if process the change paths
            if (HMWP_Classes_Tools::getOption('hmwp_hide_loggedusers')
                || (function_exists('is_user_logged_in') && !is_user_logged_in() )
            ) {
                return true;
            }

        }

        return false;
    }

    /**
     * Check if it's valid for hiding and disable things in site
     *
     * @return bool
     */
    public static function doHideDisable()
    {

        //Check if is valid for moving on
        if(!apply_filters('hmwp_process_hide_disable', true)) {
            return false;
        }

        if (defined('DOING_CRON') && DOING_CRON ) {
            return false;
        }

        //If not admin
        if (!is_admin() && !is_network_admin() ) {
            //if process the change paths
            if (HMWP_Classes_Tools::getOption('hmwp_hide_loggedusers')
                || (function_exists('is_user_logged_in') && !is_user_logged_in() )
            ) {
                return true;
            }

        }

        return false;
    }

	/**
	 * Check if it's valid for click disabl, source code and inspect element
	 *
	 * @return bool
	 */
	public static function doDisableClick()
	{

		//Check if is valid for moving on
		if(!apply_filters('hmwp_process_hide_disable', true)) {
			return false;
		}

		if (defined('DOING_CRON') && DOING_CRON ) {
			return false;
		}

		//If not admin
		if (!is_admin() && !is_network_admin() ) {

			if(function_exists('is_user_logged_in')
			   && (HMWP_Classes_Tools::getOption('hmwp_disable_click')
			       || HMWP_Classes_Tools::getOption('hmwp_disable_inspect')
			       || HMWP_Classes_Tools::getOption('hmwp_disable_source')
			       || HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste')
			       || HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop'))){

				return true;
			}

		}

		return false;
	}

    /**
     * Check if the option to hide the URLs is active
     *
     * @return bool
     */
    public static function doHideURLs()
    {

        //Check if is valid for moving on
        if(!apply_filters('hmwp_process_hide_urls', true)) {
            return false;
        }

        //Only if the user login can be verified
        if (!function_exists('is_user_logged_in')) {
            return false;
        }

        if(!isset($_SERVER['REQUEST_URI'])) {
            return false;
        }

        if (defined('DOING_CRON') && DOING_CRON ) {
            return false;
        }

        return true;
    }


    /**
     * Get the plugin settings URL
     *
     * @param string $page
     * @param string $relative
     *
     * @return string
     */
    public static function getSettingsUrl( $page = 'hmwp_settings', $relative = false )
    {
        if ($relative ) {
            return 'admin.php?page=' . $page;
        } else {
            if (!self::isMultisites() ) {
                return admin_url('admin.php?page=' . $page);
            } else {
                return network_admin_url('admin.php?page=' . $page);
            }
        }
    }

    public static function getCloudUrl($page = 'login')
    {
        return _HMWP_ACCOUNT_SITE_ . '/user/auth/' . $page;
    }

    /**
     * Get the config file for WordPress
     *
     * @return string
     */
    public static function getConfigFile()
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        if ($wp_filesystem->exists(self::getRootPath() . 'wp-config.php') ) {
            return self::getRootPath() . 'wp-config.php';
        }

        if ($wp_filesystem->exists(dirname(ABSPATH) . '/wp-config.php') ) {
            return dirname(ABSPATH) . '/wp-config.php';
        }

        return false;
    }

    /**
     * Set the header type
     *
     * @param string $type
     */
    public static function setHeader( $type )
    {
        switch ( $type ) {
        case 'json':
            header('Content-Type: application/json');
            break;
        case 'html':
            header("Content-type: text/html");
            break;
        case 'text':
            header("Content-type: text/plain");
            break;
        }
    }

    /**
     * Get a value from $_POST / $_GET
     * if unavailable, take a default value
     *
     * @param string  $key           Value key
     * @param boolean $keep_newlines Keep the new lines in variable in case of texareas
     * @param mixed   $defaultValue  (optional)
     *
     * @return array|false|string Value
     */
    public static function getValue( $key = null, $defaultValue = false, $keep_newlines = false )
    {
        if (!isset($key) || $key == '' ) {
            return false;
        }

        //Get the parameters based on the form method
        //Sanitize each parameter based on the parameter type
        $ret = (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $defaultValue));

        if (is_string($ret) === true ) {
            if ($keep_newlines === false ) {
                //Validate the param based on its type
                if (in_array($key, array('hmwp_email_address', 'hmwp_email', 'whitelist_ip', 'banlist_ip')) ) { //validate email address
                    $ret = preg_replace('/[^A-Za-z0-9-_.#*@\/]/', '', $ret);
                } elseif (in_array($key, array('hmwp_disable_name')) ) { //validate url parameter
                    $ret = preg_replace('/[^A-Za-z0-9-_]/', '', $ret);
                } elseif (in_array($key, array('hmwp_admin_url','hmwp_login_url')) ) { //validate url parameter
                    $ret = preg_replace('/[^A-Za-z0-9-_.]/', '', $ret);
                } else {
                    $ret = preg_replace('/[^A-Za-z0-9-_.\/]/', '', $ret); //validate fields
                }
                //Sanitize the text field
                $ret = sanitize_text_field($ret);

            } else {

                //Validate the textareas
                $ret = preg_replace('/[^A-Za-z0-9-_.*#\n\r\s\/]@/', '', $ret);

                //Sanitize the textarea
                if (function_exists('sanitize_textarea_field') ) {
                    $ret = sanitize_textarea_field($ret);
                }
            }
        }

        //Return the unsplas validated and sanitized value
        return wp_unslash($ret);
    }

    /**
     * Check if the parameter is set
     *
     * @param string $key
     *
     * @return boolean
     */
    public static function getIsset( $key = null )
    {
        if (!isset($key) || $key == '' ) {
            return false;
        }

        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    /**
     * Show the notices to WP
     *
     * @param string $message
     * @param string $type
     *
     * @return string
     */
    public static function showNotices( $message, $type = '' )
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        if ($wp_filesystem->exists(_HMWP_THEME_DIR_ . 'Notices.php') ) {
            ob_start();
                include _HMWP_THEME_DIR_ . 'Notices.php';
                $message = ob_get_contents();
            ob_end_clean();
        }

        return $message;
    }

    /**
     * Connect remote with wp_remote_get
     *
     * @param $url
     * @param array $params
     * @param array $options
     *
     * @return bool|string
     */
    public static function hmwp_remote_get( $url, $params = array(), $options = array() )
    {

        $parameters = '';
        if (!empty($params) ) {
            foreach ( $params as $key => $value ) {
                if ($key <> '' ) {
                    $parameters .= ($parameters == "" ? "" : "&") . $key . "=" . $value;
                }
            }

            if ($parameters <> '' ) {
                $url .= ((strpos($url, "?") === false) ? "?" : "&") . $parameters;
            }
        }

        $response = self::hmwp_wpcall($url, $params, $options);

        if (is_wp_error($response) ) { return false;
        }

        return self::cleanResponce(wp_remote_retrieve_body($response)); //clear and get the body

    }


    /**
     * Connect remote with wp_remote_get
     *
     * @param $url
     * @param array $params
     * @param array $options
     *
     * @return bool|string
     */
    public static function hmwp_remote_post( $url, $params = array(), $options = array() )
    {
        $options['method'] = 'POST';

        $response = self::hmwp_wpcall($url, $params, $options);

        if (is_wp_error($response) ) { return false;
        }

        return self::cleanResponce(wp_remote_retrieve_body($response)); //clear and get the body

    }

    /**
     * Use the WP remote call
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public static function hmwp_wpcall( $url, $params, $options )
    {
        //predefined options
        $options = array_replace_recursive(
            array(
                'sslverify' => _HMWP_CHECK_SSL_,
                'method' => 'GET',
                'timeout' => 30,
            ),
            $options
        );

        if ($options['method'] == 'POST' ) {

            $options['body'] = $params;
            unset($options['method']);
            $response = wp_remote_post($url, $options);

        } else {

            unset($options['method']);
            $response = wp_remote_get($url, $options);

        }

        if (is_wp_error($response) ) {
            //For debugging
            do_action('hmwp_debug_request', $url, $options, $response);
        }

        return $response;
    }

    /**
     * Call the local URLs for Security Check
     *
     * @param $url
     * @param $options
     * @return array|WP_Error
     */
    public static function hmwp_localcall( $url, $options = array() )
    {
        //predefined options
        $options = array_merge(
            array(
                'sslverify' => false,
                'timeout' => 10,
            ),
            $options
        );

        $response = wp_remote_get($url, $options);

        if (is_wp_error($response) ) {
            //For debugging
            do_action('hmwp_debug_local_request', $url, $options, $response);
        }

        return $response;
    }

    /**
     * Get the Json from responce if any
     *
     * @param string $response
     *
     * @return string
     */
    private static function cleanResponce( $response )
    {
        return trim($response, '()');
    }

    /**
     * Check if HTML Headers to prevent chenging the code for other file extension
     *
     * @param array $types
     *
     * @return bool
     * @throws Exception
     */
    public static function isContentHeader( $types = array('text/html', 'text/xml') )
    {
        $headers = headers_list();

        //check the Content Type
        if(!empty($headers) && !empty($types)) {
            foreach ($headers as $value) {
                if (strpos($value, ':') !== false) {
                    if (stripos($value, 'Content-Type') !== false) {

                        foreach ($types as $type) {
                            if (stripos($value, $type) !== false) {
                                return true;
                            }
                        }

                        return false;

                    }
                }
            }
        }

        return false;
    }


    /**
     * Returns true if server is Apache
     *
     * @return boolean
     */
    public static function isApache()
    {
        global $is_apache;

		//If custom defined
        if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
	       if(in_array(HMWP_Classes_Tools::getOption('hmwp_server_type'), array(
			   'nginx', 'iis', 'cloudpanel', 'flywheel'
	       ))){
			   return false;
	       }
        }

        //If custom defined
        if (defined('HMWP_SERVER_TYPE') && strtolower(HMWP_SERVER_TYPE) == 'apache' ) {
            return true;
        }

        if (self::isFlywheel() ) { //force Nginx on Flywheel server
            return false;
        }

        return $is_apache;
    }

    /**
     * Check if mode rewrite is on
     *
     * @return bool
     */
    public static function isModeRewrite()
    {
        if (function_exists('apache_get_modules') ) {
            $modules = apache_get_modules();
            if (!empty($modules) ) {
                return in_array('mod_rewrite', $modules);
            }
        }

        return true;
    }

    /**
     * Check whether server is LiteSpeed
     *
     * @return bool
     */
    public static function isLitespeed()
    {
        $litespeed = false;

	    //If custom defined
	    if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
		    return (HMWP_Classes_Tools::getOption('hmwp_server_type') == 'litespeed');
	    }

        //If custom defined
        if (defined('HMWP_SERVER_TYPE') && strtolower(HMWP_SERVER_TYPE) == 'litespeed' ) {
            return true;
        }

        if (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false ) {
            $litespeed = true;
        } elseif (isset($_SERVER['SERVER_NAME']) && stripos($_SERVER['SERVER_NAME'], 'LiteSpeed') !== false ) {
            $litespeed = true;
        } elseif (isset($_SERVER['X-Litespeed-Cache-Control']) ) {
            $litespeed = true;
        }

        if (self::isFlywheel() ) {
            return false;
        }

        return $litespeed;
    }

    /**
     * Check whether server is Lighthttp
     *
     * @return bool
     */
    public static function isLighthttp()
    {
        return (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'lighttpd') !== false);
    }

    /**
     * Check whether server is AWS StoreFront Bitnami
     *
     * @return bool
     */
    public static function isAWS()
    {
	    //If custom defined
	    if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
		    return (HMWP_Classes_Tools::getOption('hmwp_server_type') == 'bitnami');
	    }

	    if(isset($_SERVER["DOCUMENT_ROOT"]) && strpos($_SERVER["DOCUMENT_ROOT"], "/bitnami/")){
		    return true;
	    }

        $headers = headers_list();

        foreach ($headers as $header){
            if(strpos($header, 'x-amz-cf-id') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if multisites
     *
     * @return bool
     */
    public static function isMultisites()
    {
        return is_multisite();
    }

    /**
     * Check if multisites with path
     *
     * @return bool
     */
    public static function isMultisiteWithPath()
    {
        return (is_multisite() && ((defined('SUBDOMAIN_INSTALL') && !SUBDOMAIN_INSTALL) || (defined('VHOST') && VHOST == 'no')));
    }

    /**
     * Returns true if server is nginx
     *
     * @return boolean
     */
    public static function isNginx()
    {
        global $is_nginx;

	    //If custom defined
	    if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
		    if(in_array(HMWP_Classes_Tools::getOption('hmwp_server_type'), array(
			    'apache','iis'
		    ))){
			    return false;
		    }
	    }

        //If custom defined
        if (defined('HMWP_SERVER_TYPE') && strtolower(HMWP_SERVER_TYPE) == 'nginx' ) {
            return true;
        }

        return ($is_nginx ||
                (isset($_SERVER['SERVER_SOFTWARE']) &&
                 (stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false || stripos($_SERVER['SERVER_SOFTWARE'], 'TasteWP') !== false) ));
    }

    /**
     * Returns true if server is Wpengine
     *
     * @return boolean
     */
    public static function isWpengine()
    {
	    //If custom defined
	    if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
		    return (HMWP_Classes_Tools::getOption('hmwp_server_type') == 'wpengine');
	    }

        //If custom defined
        if (defined('HMWP_SERVER_TYPE') && strtolower(HMWP_SERVER_TYPE) == 'wpengine' ) {
            return true;
        }

        return (isset($_SERVER['WPENGINE_PHPSESSIONS']));
    }


    /**
     * Returns true if server is Wpengine
     *
     * @return boolean
     */
    public static function isFlywheel()
    {

	    //If custom defined
	    if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
		    return (HMWP_Classes_Tools::getOption('hmwp_server_type') == 'flywheel');
	    }

        //If custom defined
        if (defined('HMWP_SERVER_TYPE') && strtolower(HMWP_SERVER_TYPE) == 'flywheel' ) {
            return true;
        }

	    if (isset($_SERVER['SERVER']) && stripos($_SERVER['SERVER'], 'Flywheel') !== false) {
		    return true;
	    }

        return (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'Flywheel') !== false);
    }

    /**
     * Returns true if server is Inmotion
     *
     * @return boolean
     */
    public static function isInmotion()
    {

	    //If custom defined
	    if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
		    return (HMWP_Classes_Tools::getOption('hmwp_server_type') == 'inmotion');
	    }

        //If custom defined
        if (defined('HMWP_SERVER_TYPE') && strtolower(HMWP_SERVER_TYPE) == 'inmotion' ) {
            return true;
        }

        return (isset($_SERVER['SERVER_ADDR']) && stripos(@gethostbyaddr($_SERVER['SERVER_ADDR']), 'inmotionhosting.com') !== false);
    }

    /**
     * Returns true if server is Godaddy
     *
     * @return boolean
     */
    public static function isGodaddy()
    {

	    //If custom defined
	    if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
		    return (HMWP_Classes_Tools::getOption('hmwp_server_type') == 'godaddy');
	    }

        //If custom defined
        if (defined('HMWP_SERVER_TYPE') && strtolower(HMWP_SERVER_TYPE) == 'godaddy' ) {
            return true;
        }

        return (file_exists(ABSPATH . 'gd-config.php'));
    }

    /**
     * Returns true if server is IIS
     *
     * @return boolean
     */
    public static function isIIS()
    {
        global $is_IIS, $is_iis7;

	    //If custom defined
	    if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
		    return (HMWP_Classes_Tools::getOption('hmwp_server_type') == 'iis');
	    }

	    //If custom defined
        if (defined('HMWP_SERVER_TYPE') && strtolower(HMWP_SERVER_TYPE) == 'iis' ) {
            return true;
        }

        return ($is_iis7 || $is_IIS || (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'microsoft-iis') !== false));
    }

    /**
     * Returns true if windows
     *
     * @return bool
     */
    public static function isWindows()
    {
        return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }

    /**
     * Check if IIS has rewritten 2 structure enabled
     *
     * @return bool
     */
    public static function isPHPPermalink()
    {
        if (get_option('permalink_structure') ) {
            if (strpos(get_option('permalink_structure'), 'index.php') !== false || stripos(get_option('permalink_structure'), 'index.html') !== false || strpos(get_option('permalink_structure'), 'index.htm') !== false ) {
                return true;
            }
        }

        return false;
    }

	/**
	 * Returns true if server is Godaddy
	 *
	 * @return boolean
	 */
	public static function isCloudPanel()
	{

		//If custom defined
		if (HMWP_Classes_Tools::getOption('hmwp_server_type') <> 'auto' ) {
			return (HMWP_Classes_Tools::getOption('hmwp_server_type') == 'cloudpanel');
		}

		return false;
	}

    /**
     * Is a cache plugin installed in WordPress?
     *
     * @return bool
     */
    public static function isCachePlugin()
    {
        return (HMWP_Classes_Tools::isPluginActive('autoptimize/autoptimize.php') ||
            HMWP_Classes_Tools::isPluginActive('beaver-builder-lite-version/fl-builder.php') ||
            HMWP_Classes_Tools::isPluginActive('beaver-builder/fl-builder.php') ||
            HMWP_Classes_Tools::isPluginActive('breeze/breeze.php') ||
            HMWP_Classes_Tools::isPluginActive('cache-enabler/cache-enabler.php') ||
            HMWP_Classes_Tools::isPluginActive('comet-cache/comet-cache.php') ||
            HMWP_Classes_Tools::isPluginActive('hummingbird-performance/wp-hummingbird.php') ||
            HMWP_Classes_Tools::isPluginActive('hyper-cache/plugin.php') ||
            HMWP_Classes_Tools::isPluginActive('jch-optimize/jch-optimize.php') ||
            HMWP_Classes_Tools::isPluginActive('litespeed-cache/litespeed-cache.php') ||
            HMWP_Classes_Tools::isPluginActive('powered-cache/powered-cache.php') ||
            HMWP_Classes_Tools::isPluginActive('sg-cachepress/sg-cachepress.php') ||
            HMWP_Classes_Tools::isPluginActive('w3-total-cache/w3-total-cache.php') ||
            HMWP_Classes_Tools::isPluginActive('wp-asset-clean-up/wpacu.php') ||
            HMWP_Classes_Tools::isPluginActive('wp-fastest-cache/wpFastestCache.php') ||
            HMWP_Classes_Tools::isPluginActive('wp-rocket/wp-rocket.php') ||
            HMWP_Classes_Tools::isPluginActive('wp-super-cache/wp-cache.php') ||
            HMWP_Classes_Tools::isPluginActive('swift-performance/performance.php') ||
            HMWP_Classes_Tools::isPluginActive('swift-performance-lite/performance.php') ||
            WP_CACHE);
    }

    /**
     * Check whether the plugin is active by checking the active_plugins list.
     *
     * @source wp-admin/includes/plugin.php
     *
     * @param string $plugin Plugin folder/main file.
     *
     * @return boolean
     */
    public static function isPluginActive( $plugin )
    {

        if (empty(self::$active_plugins) ) {
            self::$active_plugins = (array)get_option('active_plugins', array());

            if (self::isMultisites() ) {

                if (! function_exists('get_plugins') ) {
                    include_once ABSPATH . 'wp-admin/includes/plugin.php';
                }

                self::$active_plugins = array_keys(get_plugins());
            }

        }

        return in_array($plugin, self::$active_plugins, true);
    }

	/**
	 * Check whether the theme is active.
	 *
	 * @param string $name Theme folder/main file.
	 *
	 * @return boolean
	 */
	public static function isThemeActive( $name )
	{
		$theme = get_option( 'template' );

		if ($theme) {
			if (strtolower($theme) == strtolower($name) ||
			    strtolower($theme) == strtolower($name) . ' child' ||
			    strtolower($theme) == strtolower($name) . ' child theme') {
				return true;
			}
		}

		return false;
	}

    /**
     * Get all the plugin names
     *
     * @return array
     */
    public static function getAllPlugins()
    {
        if (HMWP_Classes_Tools::getOption('hmwp_hide_all_plugins') ) {
            if (! function_exists('get_plugins') ) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $all_plugins = array_keys(get_plugins());
        } else {
            $all_plugins = (array)get_option('active_plugins', array());
        }

        if (self::isMultisites() ) {
            $all_plugins = array_merge(array_values($all_plugins), array_keys(get_site_option('active_sitewide_plugins')));
        }

        return $all_plugins;
    }

    /**
     * Get all the themes names
     *
     * @return array
     */
    public static function getAllThemes()
    {
        return search_theme_directories();
    }

	/**
	 * Get the absolute filesystem path to the root of the WordPress installation
	 *
	 * @return string Full filesystem path to the root of the WordPress installation
	 */
	public static function getRootPath()
	{
		$root_path = ABSPATH;

		if (defined('_HMWP_CONFIGPATH') ) {
			$root_path =  _HMWP_CONFIGPATH;
		} elseif (self::isFlywheel() && defined('WP_CONTENT_DIR') && dirname(WP_CONTENT_DIR) ) {
			$root_path =  str_replace('\\', '/', dirname(WP_CONTENT_DIR)) . '/';
		}

		return apply_filters('hmwp_root_path', $root_path);

	}

	/**
	 * Get the absolute filesystem path to the root of the WordPress installation
	 *
	 * @return string Full filesystem path to the root of the WordPress installation
	 */
	public static function getHomeRootPath()
	{
		$home_root = '/';
		if(HMWP_Classes_Tools::isMultisites() && defined('PATH_CURRENT_SITE')){
			$path = PATH_CURRENT_SITE;
		}else {
			$path = parse_url(site_url(), PHP_URL_PATH);
		}

		if ($path) {
			$home_root = trailingslashit($path);
		}

		return apply_filters('hmwp_home_root', $home_root);
	}

    /**
     * Get Relative path for the current blog in case of WP Multisite
     *
     * @param $url
     *
     * @return string
     */
    public static function getRelativePath( $url )
    {
        $url = wp_make_link_relative($url);

        if ($url <> '' ) {
            $url = str_replace(wp_make_link_relative(get_bloginfo('url')), '', $url);

            if (self::isMultisiteWithPath() && defined('PATH_CURRENT_SITE') && PATH_CURRENT_SITE <> '/' ) {
                $url = str_replace(rtrim(PATH_CURRENT_SITE, '/'), '', $url);
            }
        }

        return trailingslashit($url);
    }

	/**
	 * Check if wp-content is changed and set in a different location
	 *
	 * @ver 7.0.12
	 *
	 * @return bool
	 */
	public static function isDifferentWPContentPath(){
		$homepath = '';
		if(parse_url(site_url(), PHP_URL_PATH)){
			$homepath = ltrim(parse_url(site_url(), PHP_URL_PATH), '/');
		}

		if($homepath <> '/') {
			$contenturl = ltrim(parse_url(content_url(), PHP_URL_PATH), '/');

			return (strpos($contenturl, $homepath . '/') === false);
		}

		return false;
	}

    /**
     * Empty the cache from other cache plugins when save the settings
     */
    public static function emptyCache()
    {

        try {
            //Empty WordPress rewrites count for 404 error.
            //This happens when the rules are not saved through config file
            HMWP_Classes_Tools::saveOptions('file_mappings', array());

            //For debugging
            do_action('hmwp_debug_cache', '');

	        if (class_exists('\FlyingPress\Purge') && method_exists('\FlyingPress\Purge', 'purge_everything')){
		        \FlyingPress\Purge::purge_everything();
	        }

            if (class_exists('\JchOptimize\Platform\Cache') && method_exists('\JchOptimize\Platform\Cache', 'deleteCache') ) {
                \JchOptimize\Platform\Cache::deleteCache();
            }

            if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all') ) {
                \LiteSpeed_Cache_API::purge_all();
            }
            //////////////////////////////////////////////////////////////////////////////
            if (function_exists('w3tc_pgcache_flush') ) {
                w3tc_pgcache_flush();
            }

            if (function_exists('w3tc_minify_flush') ) {
                w3tc_minify_flush();
            }
            if (function_exists('w3tc_dbcache_flush') ) {
                w3tc_dbcache_flush();
            }
            if (function_exists('w3tc_objectcache_flush') ) {
                w3tc_objectcache_flush();
            }
            //////////////////////////////////////////////////////////////////////////////

            if (function_exists('wp_cache_clear_cache') ) {
                wp_cache_clear_cache();
            }

            if (function_exists('rocket_clean_domain') && function_exists('rocket_clean_minify') && function_exists('rocket_clean_cache_busting') ) {
                // Remove all cache files
                rocket_clean_domain();
                rocket_clean_minify();
                rocket_clean_cache_busting();
            }
            //////////////////////////////////////////////////////////////////////////////

            if (function_exists('apc_clear_cache') ) {
                // Remove all apc if enabled
                apc_clear_cache();
            }
            //////////////////////////////////////////////////////////////////////////////

            if (class_exists('Cache_Enabler_Disk') && method_exists('Cache_Enabler_Disk', 'clear_cache') ) {
                // clear disk cache
                Cache_Enabler_Disk::clear_cache();
            }
            //////////////////////////////////////////////////////////////////////////////

            if (class_exists('LiteSpeed_Cache') ) {
                LiteSpeed_Cache::get_instance()->purge_all();
            }
            //////////////////////////////////////////////////////////////////////////////

            if (self::isPluginActive('hummingbird-performance/wp-hummingbird.php') ) {
                do_action('wphb_clear_page_cache');
            }
            //////////////////////////////////////////////////////////////////////////////

            if (class_exists('WpeCommon') ) {
                if (method_exists('WpeCommon', 'purge_memcached') ) {
                    WpeCommon::purge_memcached();
                }
                if (method_exists('WpeCommon', 'clear_maxcdn_cache') ) {
                    WpeCommon::clear_maxcdn_cache();
                }
                if (method_exists('WpeCommon', 'purge_varnish_cache') ) {
                    WpeCommon::purge_varnish_cache();
                }
            }
            //////////////////////////////////////////////////////////////////////////////

            if (self::isPluginActive('sg-cachepress/sg-cachepress.php') && class_exists('Supercacher') ) {
                if (method_exists('Supercacher', 'purge_cache') && method_exists('Supercacher', 'delete_assets') ) {
                    Supercacher::purge_cache();
                    Supercacher::delete_assets();
                }
            }

            //Clear the fastest cache
            global $wp_fastest_cache;
            if (isset($wp_fastest_cache) && method_exists($wp_fastest_cache, 'deleteCache') ) {
                $wp_fastest_cache->deleteCache();
            }
            //////////////////////////////////////////////////////////////////////////////
        } catch ( Exception $e ) {

        }

    }

    /**
     * Flush the WordPress rewrites
     */
    public static function flushWPRewrites()
    {
        if (HMWP_Classes_Tools::isPluginActive('woocommerce/woocommerce.php') ) {
            update_option('woocommerce_queue_flush_rewrite_rules', 'yes');
        }

    }

    /**
     * Called on plugin activation
     *
     * @throws Exception
     */
    public function hmwp_activate()
    {
        set_transient('hmwp_activate', true);

        //set restore settings option on plugin activate
        $lastsafeoptions = self::getOptions(true);
        if (isset($lastsafeoptions['hmwp_mode']) && ($lastsafeoptions['hmwp_mode'] == 'ninja' || $lastsafeoptions['hmwp_mode'] == 'lite') ) {
            set_transient('hmwp_restore', true);
        }

        //Initialize the compatibility with other plugins
        HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->install();
    }

    /**
     * Called on plugin deactivation
     * Remove all the rewrite rules on deactivation
     *
     * @throws Exception
     */
    public function hmwp_deactivate()
    {
        $options = self::$default;
        //Prevent duplicates
        foreach ( $options as $key => $value ) {
            //set the default params from tools
            self::saveOptions($key, $value);
        }

        //remove user capability
        HMWP_Classes_ObjController::getClass('HMWP_Models_RoleManager')->removeHMWPCaps();

        //remove the custom rules
        HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeToFile('', 'HMWP_VULNERABILITY');
        HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeToFile('', 'HMWP_RULES');

        //clear the locked ips
        HMWP_Classes_ObjController::getClass('HMWP_Controllers_Brute')->clearBlockedIPs();

	    //Build the redirect table
	    HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushChanges();

        //Delete the compatibility with other plugins
        HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->uninstall();
    }

    /**
     * Call this function on rewrite update from other plugins
     *
     * @param array $wp_rules
     *
     * @return array
     * @throws Exception
     */
    public function checkRewriteUpdate( $wp_rules = array() )
    {
        try {
            if (!HMWP_Classes_Tools::getOption('error') && !HMWP_Classes_Tools::getOption('logout') ) {

                //Build the redirect table
                HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->clearRedirect()->setRewriteRules()->flushRewrites();

                //INSERT SEURITY RULES
                if (!HMWP_Classes_Tools::isIIS() ) {
                    //For Nginx and Apache the rules can be inserted separately
                    $rules = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getInjectionRewrite();

                    HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeToFile($rules, 'HMWP_VULNERABILITY');

                }
            }

        } catch ( Exception $e ) {

        }

        return $wp_rules;
    }

    /**
     * Check if new themes or plugins are added in WordPress
     */
    public function checkPluginsThemesUpdates()
    {

        try {
            //Check if tere are plugins added to website
            if (HMWP_Classes_Tools::getOption('hmwp_hide_plugins')) {
                $all_plugins = HMWP_Classes_Tools::getAllPlugins();
                $dbplugins = HMWP_Classes_Tools::getOption('hmwp_plugins');
                foreach ($all_plugins as $plugin) {
                    if (function_exists('is_plugin_active') && is_plugin_active($plugin) && isset($dbplugins['from']) && !empty($dbplugins['from'])) {
                        if (!in_array(plugin_dir_path($plugin), $dbplugins['from'])) {
                            HMWP_Classes_Tools::saveOptions('changes', true);
                        }
                    }
                }
            }

            //Check if there are themes added to website
            if (HMWP_Classes_Tools::getOption('hmwp_hide_themes')) {

                //Initialize WordPress Filesystem
                $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

                $all_themes = HMWP_Classes_Tools::getAllThemes();
                $dbthemes = HMWP_Classes_Tools::getOption('hmwp_themes');
                foreach ($all_themes as $theme => $value) {
                    if ($wp_filesystem->is_dir($value['theme_root']) && isset($dbthemes['from']) && !empty($dbthemes['from'])) {
                        if (!in_array($theme . '/', $dbthemes['from'])) {
                            HMWP_Classes_Tools::saveOptions('changes', true);
                        }
                    }
                }
            }

            //If there are changed (new plugins, new themes)
            if (self::getOption('changes')) {
                //Initialize the compatibility with other plugins
                HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->install();
            }
        }catch (Exception $e){

        }
    }

	/**
	 * Call Account API Server
	 *
	 * @param string $email
	 * @param string $redirect_to
	 *
	 * @return array|mixed|void
	 */
    public static function checkAccountApi( $email = null, $redirect_to = '' )
    {

	    $check        = array();
	    $monitor      = HMWP_Classes_Tools::getValue( 'hmwp_monitor', 0 );
	    $domain = (self::isMultisites() && defined('BLOG_ID_CURRENT_SITE')) ? get_home_url(BLOG_ID_CURRENT_SITE) : home_url();

	    if ( isset( $email ) && $email <> '' ) {
		    $args     = array(
			    'email'        => $email,
			    'url'          => $domain,
			    'howtolessons' => 1,
			    'monitor'      => (int) $monitor,
			    'source'       => 'hide-my-wp'
		    );
		    $response = HMWP_Classes_Tools::hmwp_remote_get( _HMWP_API_SITE_ . '/api/free/token', $args, array( 'timeout' => 10 ) );
	    } elseif ( HMWP_Classes_Tools::getOption( 'hmwp_token' ) ) {
		    $args     = array(
			    'token'        => self::getOption( 'hmwp_token' ),
			    'url'          => $domain,
			    'howtolessons' => 1,
			    'monitor'      => (int) $monitor,
			    'source'       => 'hide-my-wp'
		    );
		    $response = HMWP_Classes_Tools::hmwp_remote_get( _HMWP_API_SITE_ . '/api/free/token', $args, array( 'timeout' => 10 ) );
	    } else {
		    return $check;
	    }

	    if ( $response && json_decode( $response ) ) {
			//print_r($response);exit();
		    $check = json_decode( $response, true );

		    HMWP_Classes_Tools::saveOptions( 'hmwp_token', ( isset( $check['token'] ) ? $check['token'] : 0 ) );
		    HMWP_Classes_Tools::saveOptions( 'api_token', ( isset( $check['api_token'] ) ? $check['api_token'] : false ) );
		    HMWP_Classes_Tools::saveOptions( 'error', isset( $check['error'] ) );

		    if ( ! isset( $check['error'] ) ) {
			    if ( $redirect_to <> '' ) {
				    wp_redirect( $redirect_to );
				    exit();
			    }
		    } elseif ( isset( $check['message'] ) ) {
			    HMWP_Classes_Error::setError( $check['message'] );
		    }
	    } else {
		    HMWP_Classes_Error::setError( sprintf( __( 'CONNECTION ERROR! Make sure your website can access: %s', 'hide-my-wp' ), '<a href="' . _HMWP_ACCOUNT_SITE_ . '" target="_blank">' . _HMWP_ACCOUNT_SITE_ . '</a>' ) . " <br /> " );
	    }

	    return $check;

    }

    /**
     * Verify the API response on update
     *
     * @param  $result
     */
    public function checkLicenseOnUpdate($result)
    {

        // check the token
        if (!self::getOption('hmwp_token') ) {
            return;
        }

        if($body = json_decode(wp_remote_retrieve_body($result))) {

            //if data received is valid
            HMWP_Classes_Tools::saveOptions('hmwp_valid', 1);

            if (isset($body->expires) && (int)$body->expires > 0 && (int)$body->expires < time()) {
                HMWP_Classes_Tools::saveOptions('hmwp_valid', 0);
                HMWP_Classes_Tools::saveOptions('hmwp_expires', $body->expires);
            }elseif(isset($body->download_url) && !$body->download_url) {
                HMWP_Classes_Tools::saveOptions('hmwp_valid', 0);
                HMWP_Classes_Tools::saveOptions('hmwp_expires', 0);
            }

        }else{
            HMWP_Classes_Tools::saveOptions('hmwp_valid', 0);
            HMWP_Classes_Tools::saveOptions('hmwp_expires', 0);
        }

    }

    /**
     * Send the email is case there are major changes
     *
     * @return bool
     */
    public static function sendEmail()
    {
        $email = self::getOption('hmwp_email_address');
        if ($email == '' ) {
            global $current_user;
            $email = $current_user->user_email;
        }

        $line = "\n" . "________________________________________" . "\n";
        $to = $email;
        $subject = self::getOption('hmwp_plugin_name') . ' - ' . esc_html__('New Login Data', 'hide-my-wp');
        $message = sprintf(esc_html__("Thank you for using %s!", 'hide-my-wp'), self::getOption('hmwp_plugin_name')) . "\n";
        $message .= $line;
        $message .= esc_html__("Your new site URLs are", 'hide-my-wp') . ':' . "\n";
        $message .= esc_html__("Admin URL", 'hide-my-wp') . ': ' . admin_url() . "\n";
        $message .= esc_html__("Login URL", 'hide-my-wp') . ': '  . site_url(self::$options['hmwp_login_url']) . "\n";
        $message .= $line;
        $message .= esc_html__("Note: If you can't login to your site, just access this URL", 'hide-my-wp') . ':' . "\n";
        $message .= site_url() . "/wp-login.php?" . self::getOption('hmwp_disable_name') . "=" . self::$options['hmwp_disable'] . "\n\n";
        $message .= $line;
        $message .= esc_html__("Best regards", 'hide-my-wp') . ',' . "\n";
        $message .= self::getOption('hmwp_plugin_name') . "\n";

        $headers = array();
        $headers[] = sprintf(esc_html__("From: %s <%s>", 'hide-my-wp'), self::getOption('hmwp_plugin_name'), $email);
        $headers[] = 'Content-type: text/plain';

        add_filter('wp_mail_content_type', array('HMWP_Classes_Tools', 'setContentType'));

        if (@wp_mail($to, $subject, $message, $headers) ) {
            return true;
        }

        return false;
    }

    /**
     * Set the content type to text/plain
     *
     * @return string
     */
    public static function setContentType()
    {
        return "text/plain";
    }

        /**
         * Set the current user role for later use
         *
         * @param WP_User $user
         *
         * @return string
         */
    public static function setCurrentUserRole( $user = null )
    {
        $roles = array();

        if (isset($user) && isset($user->roles) && is_array($user->roles) ) {
            $roles = $user->roles;
        }elseif (function_exists('wp_get_current_user') ) {
            $user = wp_get_current_user();

            if(isset($user->roles) && is_array($user->roles)) {
                $roles = $user->roles;
            }
        }

        if (!empty($roles) ) {
            self::$current_user_role = current($roles);
        }

        return self::$current_user_role;
    }

    /**
     * Get the user main Role or default
     *
     * @return string
     */
    public static function getUserRole()
    {
        return self::$current_user_role;
    }

    /**
     * Check the user capability for the roles attached
     *
     * @param  $cap
     * @return bool
     */
    public static function userCan( $cap )
    {

        if (function_exists('current_user_can') ) {

            if (current_user_can($cap) ) {
                return true;
            }

            //Get the current user roles
            $user = wp_get_current_user();

            //If the user has multiple roles
            if (isset($user->roles) && is_array($user->roles) && count($user->roles) > 1 ) {
                foreach ( $user->roles as $role ) {

                    //Get the role
                    $role_object = get_role($role);

                    //Check if it has capability
                    if ($role_object->has_cap($cap) ) {
                        return true;
                    }
                }
            }

        }

        return false;
    }


    /**
     * Customize the redirect for the logout process
     *
     * @param  $redirect
     * @return mixed
     */
    public static function getCustomLogoutURL( $redirect )
    {
        //Get Logout based on user Role
        $role = HMWP_Classes_Tools::getUserRole();
        $urlRedirects = HMWP_Classes_Tools::getOption('hmwp_url_redirects');
        if (isset($urlRedirects[$role]['logout']) && $urlRedirects[$role]['logout'] <> '' ) {
            $redirect = $urlRedirects[$role]['logout'];
        } elseif (isset($urlRedirects['default']['logout']) && $urlRedirects['default']['logout'] <> '' ) {
            $redirect = $urlRedirects['default']['logout'];
        }

        return $redirect;
    }

    /**
     * Customize the redirect for the login process
     *
     * @param string $redirect
     * @return string
     */
    public static function getCustomLoginURL( $redirect )
    {

        //Get Logout based on user Role
        $role = HMWP_Classes_Tools::getUserRole();
        $urlRedirects = HMWP_Classes_Tools::getOption('hmwp_url_redirects');
        if (isset($urlRedirects[$role]['login']) && $urlRedirects[$role]['login'] <> '' ) {
            $redirect = $urlRedirects[$role]['login'];
        } elseif (isset($urlRedirects['default']['login']) && $urlRedirects['default']['login'] <> '' ) {
            $redirect = $urlRedirects['default']['login'];
        }

        return $redirect;
    }

    /**
     * Generate a string
     *
     * @param  int $length
     * @return bool|string
     */
    public static function generateRandomString( $length = 10 )
    {
        return substr(str_shuffle(str_repeat($x = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }

    /**
     * make hidemywp the first plugin that loads
     */
    public static function movePluginFirst()
    {
        //Make sure the plugin is loaded first
        $plugin = dirname(HMWP_BASENAME) . '/index.php';
        $active_plugins = get_option('active_plugins');

        if (!empty($active_plugins) ) {

            $this_plugin_key = array_search($plugin, $active_plugins);

            if ($this_plugin_key > 0 ) {
                array_splice($active_plugins, $this_plugin_key, 1);
                array_unshift($active_plugins, $plugin);
                update_option('active_plugins', $active_plugins);


            }

        }
    }

    /**
     * Instantiates the WordPress filesystem
     *
     * @static
     * @access public
     * @return WP_Filesystem_Base|WP_Filesystem_Direct
     */
    public static function initFilesystem()
    {
        return HMWP_Classes_ObjController::initFilesystem();
    }

	/**
	 * Check if there are whitelisted IPs for accessing the hidden paths
	 * @return bool
	 */
	public static function isWhitelistedIP($ip){
		$wl_items = array();

		//jetpack whitelist
		$wl_jetpack = array(
			'122.248.245.244/32',
			'54.217.201.243/32',
			'54.232.116.4/32',
			'185.64.140.0/22',
			'76.74.255.0/22',
			'192.0.64.0/18',
			'192.0.65.0/22',
			'192.0.80.0/22',
			'192.0.96.0/22',
			'192.0.112.0/20',
			'192.0.123.0/22',
			'195.234.108.0/22',
		);

		if (filter_var(home_url(), FILTER_VALIDATE_URL) !== FALSE && strpos(home_url(), '.') !== false) {
			$wl_jetpack[] = '127.0.0.1';
		}

		if (HMWP_Classes_Tools::getOption('whitelist_ip')) {
			$wl_items = json_decode(HMWP_Classes_Tools::getOption('whitelist_ip'), true);
		}

		//merge all the whitelisted ips and also add the hook for users
		$wl_items = apply_filters('hmwp_whitelisted_ips', array_merge($wl_jetpack, $wl_items));

		try {
			foreach ($wl_items as $item) {
				$item = trim($item);

				if ($ip == $item) {
					return true;
				}

				if (strpos($item, '*') === false && strpos($item, '/') === false) { //no match, no wildcard
					continue;
				}

				if(strpos($ip,'.') !== false) {

					if(strpos($item,'/') !== false) {
						list( $range, $bits ) = explode( '/', $item, 2 );
						$subnet = ip2long( $range );
						$iplong = ip2long( $ip );
						$mask = -1 << (32 - $bits);
						$subnet &= $mask;

						if ( ($iplong & $mask) == $subnet ){
							return true;
						}

					}

					$iplong = ip2long($ip);
					$ip_low = ip2long(str_replace('*', '0', $item));
					$ip_high = ip2long(str_replace('*', '255', $item));

					if ($iplong >= $ip_low && $iplong <= $ip_high) {//IP is within wildcard range
						return true;
					}
				}

			}
		} catch(ArithmeticError $e) {
		}
		return false;
	}
}
