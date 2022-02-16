<?php
/**
 * Compatibility Model
 * Handles the compatibility with the rest of the plugins and themes
 *
 * @file  The Compatibility file
 * @package HMWP/Compatibility
 * @since 6.0.0
 */
defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility
{

    /**
     * Set the compatibility needed on plugin activation
     * Called on plugin activation
     */
    public function install()
    {
        if (HMWP_Classes_Tools::isPluginActive('worker/init.php') ) {
            $this->addMUPlugin();
        }
    }

    /**
     * Delete the compatibility with other plugins
     * Called on plugin deactivation
     */
    public function uninstall()
    {
        $this->deleteMUPlugin();
    }

    /**
     * Check some compatibility on page load
     */
    public function checkCompatibility()
    {

        //If Admin
        if (is_admin() ) {

            add_filter('rocket_cache_reject_uri', array($this, 'rocket_reject_url'), PHP_INT_MAX);

            //Check compatibility with Really Simple SSL
            if (HMWP_Classes_Tools::isPluginActive('really-simple-ssl/rlrsssl-really-simple-ssl.php') ) {
                add_action('hmwp_flushed_rewrites', array($this, 'checkSimpleSSLRewrites'));
            }

            //Compatibility with Nitropack - tested 22102021
            if (HMWP_Classes_Tools::isPluginActive('nitropack/main.php') ) {
                //Doesn't work when blocking CSS and JS on old paths
                add_filter(
                    'hmwp_common_paths_extensions', function ( $alltypes ) {
                        return array_diff($alltypes, array('\.css','\.scss','\.js'));
                    }
                );
            }

            //if
            if(HMWP_Classes_Tools::getValue('action') == 'wordfence_scan' && HMWP_Classes_Tools::isPluginActive('wordfence/wordfence.php')) {
                set_transient('hmwp_disable_hide_urls', 1, 3600);
            }

        } else {

            //If in preview mode of the front page
            if (HMWP_Classes_Tools::getValue('hmwp_preview') ) {
                $_COOKIE = array();
                @header_remove("Cookie");
            }

            try {

                //Remove footer content from cache plugins
                defined('WPFC_REMOVE_FOOTER_COMMENT') || define('WPFC_REMOVE_FOOTER_COMMENT', true);
                defined('WP_ROCKET_WHITE_LABEL_FOOTPRINT') || define('WP_ROCKET_WHITE_LABEL_FOOTPRINT', true);

                //Conpatibility with Confirm Email from AppThemes
                if (HMWP_Classes_Tools::isPluginActive('confirm-email/confirm-email.php') ) {
                    add_action('init', array($this, 'checkAppThemesConfirmEmail'));
                }

                //Compatibility with Assets plugin - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('wp-asset-clean-up/wpacu.php') || HMWP_Classes_Tools::isPluginActive('wp-asset-clean-up-pro/wpacu.php') ) {
                    add_filter('wpacu_html_source', array($this, 'findReplaceCache'), PHP_INT_MAX);
                }

                //Compatibility with Autoptimize plugin - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('autoptimize/autoptimize.php') ) {

                    if (HMWP_Classes_Tools::isPluginActive('wp-smush-pro/wp-smush.php') ) {
                        if ($smush = get_option('wp-smush-cdn_status') ) {
                            if (isset($smush->cdn_enabled) && $smush->cdn_enabled ) {
                                return;
                            }
                        }
                    }

                    add_filter('autoptimize_html_after_minify', array($this, 'findReplaceCache'), PHP_INT_MAX);

                }

                //Compatibility with Breeze plugin - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('breeze/breeze.php')) {
                    add_filter('breeze_minify_content_return', array($this, 'findReplaceCache'), PHP_INT_MAX);

	                $breezeOptions = get_option('breeze_file_settings');
	                if(isset($breezeOptions['breeze-minify-css']) && $breezeOptions['breeze-minify-css']){
		                add_filter('hmwp_process_find_replace', '__return_false');

		                add_filter('breeze_minify_content_return', function ($content){
			                add_filter('hmwp_process_find_replace', '__return_true');
			                return $this->findReplaceCache($content);
		                }, PHP_INT_MAX);

	                }

                }


                //Compatibility with CDN Enabler - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('cdn-enabler/cdn-enabler.php') ) {
                    add_filter('hmwp_laterload', '__return_true');
                }

                //Compatibility with Comet Cache - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('comet-cache/comet-cache.php') ) {
                    if (!defined('COMET_CACHE_DEBUGGING_ENABLE')) {
                        define('COMET_CACHE_DEBUGGING_ENABLE', false);
                    }
                }

                //Compatibility with Hummingbird plugin - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('hummingbird-performance/wp-hummingbird.php') ) {
                    add_filter('wphb_cache_content', array($this, 'findReplaceCache'), PHP_INT_MAX);
                    add_filter('template_redirect', array($this, 'removeHummingbirdComment'));
                }

                //Compatibility with Hyper Cache plugin - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('hyper-cache/plugin.php') ) {
                    add_filter('cache_buffer', array($this, 'findReplaceCache'), PHP_INT_MAX);
                }

                //Compatibility with W3 Total cache
                if (HMWP_Classes_Tools::isPluginActive('w3-total-cache/w3-total-cache.php') ) {

                    if (apply_filters('w3tc_lazyload_is_embed_script', true) ) {
                        add_filter('w3tc_lazyload_is_embed_script', '__return_false', PHP_INT_MAX);
                        add_filter('w3tc_lazyload_embed_script', array($this, 'embedW3TotalCacheLazyLoadscript'), PHP_INT_MAX);
                    }
                }

                //Add compatibility with JCH Optimize plugin
                if (HMWP_Classes_Tools::isPluginActive('jch-optimize/jch-optimize.php') ) {

                    if (!defined('JCH_CACHE_DIR')) {
                        //Initialize WordPress Filesystem
                        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();
                        define('JCH_CACHE_DIR', $wp_filesystem->wp_content_dir() . 'cache/mycache/');
                    }

                    add_filter('jch_optimize_save_content', array($this, 'findReplaceCache'), PHP_INT_MAX);
                }

                //Compatibility with LiteSpeed Cache to change the cache directory too
                if (HMWP_Classes_Tools::isPluginActive('litespeed-cache/litespeed-cache.php') ) {
                    add_filter('hmwp_priority_buffer', '__return_true');
                    add_filter('litespeed_comment', '__return_false');
                }

                //Compatibility with Wp Fastest Cache
                if (HMWP_Classes_Tools::isPluginActive('wp-fastest-cache/wpFastestCache.php') ) {
                    add_filter('wpfc_buffer_callback_filter', array($this, 'findReplaceCache'), PHP_INT_MAX);
                }

                //compatibility with Wp Maintenance plugin - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('wp-maintenance-mode/wp-maintenance-mode.php') ) {
                    add_filter('wpmm_footer', array($this, 'findReplaceBuffer'));
                }

                if(HMWP_Classes_Tools::isPluginActive('minimal-coming-soon-maintenance-mode/minimal-coming-soon-maintenance-mode.php') ) {
                    $headers = headers_list();

                    if(!empty($headers)) {
                        $iscontenttype = false;
                        foreach ($headers as $value) {
                            if (strpos($value, ':') !== false) {
                                if (stripos($value, 'Content-Type') !== false) {
                                    $iscontenttype = true;
                                }
                            }
                        }

                        if(!$iscontenttype) {
                            header('Content-Type: text/html; charset=UTF-8');
                            add_filter('hmwp_priority_buffer', '__return_true');
                        }
                    }

                }

                //compatibility with All In One WP Security - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('all-in-one-wp-security-and-firewall/wp-security.php') ) {
                    add_filter('aiowps_site_lockout_output', array($this, 'aioSecurityMaintenance'), PHP_INT_MAX, 1);
                }

                //compatibility with wp-defender on custom login - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('wp-defender/wp-defender.php') ) {
                    add_filter('wd_mask_login_enable', '__return_false', PHP_INT_MAX, 0);
                }

                //Compatibility with Oxygen - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('oxygen/functions.php') ) {
                    add_filter('hmwp_laterload', '__return_true');
                }

                //compatibility with Wp Bakery - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('js_composer/js_composer.php') ) {
                    add_filter('hmwp_option_hmwp_hide_styleids', '__return_false');
                }

                //Compatibility with Powered Cache
                if (HMWP_Classes_Tools::isPluginActive('powered-cache/powered-cache.php') ) {
                    global $powered_cache_options;

                    add_filter('powered_cache_page_caching_buffer', array($this, 'findReplaceCache'), PHP_INT_MAX);

                    if (isset($powered_cache_options) ) {
                        $powered_cache_options['show_cache_message'] = false;
                    }

                }

                //Compatibility with Squirrly SEO - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('squirrly-seo/squirrly.php') ) {
                    add_filter('sq_option_sq_minify', '__return_true');

                    add_filter('sq_buffer', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'find_replace'), PHP_INT_MAX);
                }

                //Add Compatibility with Siteguard Security plugin
                if (HMWP_Classes_Tools::isPluginActive('siteguard/siteguard.php') ) {
                    //remove custom login if already set in HMWP Ghost to prevent errors
                    add_filter(
                        "pre_option_siteguard_config", function ($siteguard_config) {
                            if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
                                $siteguard_config['renamelogin_enable'] = 0;
                            }

                            return $siteguard_config;
                        } 
                    );

                }

                //Add Compatibility with Siteguard Optimiser plugin
                if (HMWP_Classes_Tools::isPluginActive('sg-cachepress/sg-cachepress.php') ) {
                    //remove css and js combination as it gives errors when the paths are changed
                    add_filter("option_siteground_optimizer_combine_css", '__return_false');
                    add_filter("option_siteground_optimizer_combine_javascript", '__return_false');

                }

                //Compatibility with WP-rocket plugin
                if (HMWP_Classes_Tools::isPluginActive('wp-rocket/wp-rocket.php') ) {
                    //Load the cache with rocket
                    add_filter('rocket_buffer', array($this, 'findReplaceCache'), PHP_INT_MAX);

                    add_filter('rocket_cache_busting_filename', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'find_replace_url'), PHP_INT_MAX);
                    add_filter('rocket_iframe_lazyload_placeholder', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'find_replace_url'), PHP_INT_MAX);
                }

                //Patch for WOT Cache plugin
                if (defined('WOT_VERSION') ) {
                    add_filter('wot_cache', array($this, 'findReplaceCache'), PHP_INT_MAX);
                }

                //For woo-global-cart plugin
                if (defined('WOOGC_VERSION') ) {
                    remove_all_actions('shutdown', 1);
                    //Hook the cached buffer
                    add_filter('hmwp_buffer', array($this, 'fix_woogc_shutdown'));
                }


                //Change the template directory URL in themes
                if ((HMWP_Classes_Tools::isThemeActive('Avada') || HMWP_Classes_Tools::isThemeActive('WpRentals')) && !HMWP_Classes_Tools::getOption('hmwp_mapping_file') ) {
                    add_filter('template_directory_uri', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'find_replace_url'), PHP_INT_MAX);
                }


                //Compatibility with W3 Total cache - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('w3-total-cache/w3-total-cache.php') ) {
                    //Don't show comments
                    add_filter('w3tc_can_print_comment', '__return_false', PHP_INT_MAX);
                    //Hook the cached buffer
                    add_filter('w3tc_processed_content', array($this, 'findReplaceCache'), PHP_INT_MAX);
                }

                //Compatibility with Wp Super Cache Plugin - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('wp-super-cache/wp-cache.php') ) {
                    //Hook the cached buffer
                    add_filter('wpsupercache_buffer', array($this, 'findReplaceCache'), PHP_INT_MAX);
                }

                //Compatibility with Uptimate Member - tested 01102021
                if (HMWP_Classes_Tools::isPluginActive('ultimate-member/ultimate-member.php') ) {
                    add_filter('hmwp_option_hmwp_hide_login', '__return_false');
                }

                //Compatibility with XMl Sitemap - tested 02112021
                if (HMWP_Classes_Tools::getOption('hmwp_hide_in_sitemap')  && isset($_SERVER['REQUEST_URI'])) {

                    //XML Sitemap
                    if (HMWP_Classes_Tools::isPluginActive('google-sitemap-generator/sitemap.php')) {
                        add_action('sm_build_index', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'findReplaceXML'));
                        add_action('sm_build_content', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'findReplaceXML'));
                    }

                    //Yoast sitemap
                    if (HMWP_Classes_Tools::isPluginActive('wordpress-seo/wp-seo.php')) {
                        add_filter("wpseo_stylesheet_url", "__return_false");
                    }

                    //Rank Math sitemap
                    if (HMWP_Classes_Tools::isPluginActive('seo-by-rank-math/rank-math.php') ) {
                        if($type = str_replace(array('sitemap','-','_','.xml','/'), '', strtok($_SERVER["REQUEST_URI"], '?'))) {
                            if($type == 'index') { $type = 1;
                            }
                            add_filter("rank_math/sitemap/{$type}_stylesheet_url", "__return_false");
                            add_filter('rank_math/sitemap/remove_credit', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'findReplaceXML'));
                            add_filter("rank_math/sitemap/remove_credit",  "__return_true");
                        }
                    }

                    //SeoPress
                    if (HMWP_Classes_Tools::isPluginActive('wp-seopress/seopress.php') ) {
                        add_filter("seopress_sitemaps_xml_index", array($this, 'findReplaceCache'), PHP_INT_MAX);
                        add_filter("seopress_sitemaps_xml_author", array($this, 'findReplaceCache'), PHP_INT_MAX);
                        add_filter("seopress_sitemaps_xml_single_term", array($this, 'findReplaceCache'), PHP_INT_MAX);
                        add_filter("seopress_sitemaps_xml_single", array($this, 'findReplaceCache'), PHP_INT_MAX);
                    }

                    //WordPress default sitemap
                    add_filter("wp_sitemaps_stylesheet_url", "__return_false");
                    add_filter("wp_sitemaps_stylesheet_index_url", "__return_false");

                }


            } catch ( Exception $e ) { }

        }

        //Hook the Hide URLs before the plugin
        //Check params and compatibilities
        add_action(
            'init', function () {
                //Compatibility with iThemes Security, Temporary Login Plugin, LoginPress, Wordfence
                if (function_exists('is_user_logged_in') && !is_user_logged_in() && isset($_SERVER['REQUEST_URI'])) {

                    if (HMWP_Classes_Tools::getValue('action') == 'itsec-check-loopback') {
                        $exp = HMWP_Classes_Tools::getValue('exp');
                        $action = 'itsec-check-loopback';
                        $hash = hash_hmac('sha1', "$action|$exp", wp_salt());

                        if ($hash <> HMWP_Classes_Tools::getValue('hash', '')) {
                            add_filter('hmwp_process_hide_urls', '__return_false');
                        }
                    }

                    //?wtlwp_token=value
                    if (HMWP_Classes_Tools::getValue('wtlwp_token') && HMWP_Classes_Tools::isPluginActive('temporary-login-without-password/temporary-login-without-password.php')) {
                        add_filter('hmwp_process_hide_urls', '__return_false');
                    }

                    //?aam-jwt=value
                    if (HMWP_Classes_Tools::getValue('aam-jwt') && HMWP_Classes_Tools::isPluginActive('advanced-access-manager/aam.php')) {
                        add_filter('hmwp_process_hide_urls', '__return_false');
                    }

                    //?loginpress_code=value
                    if (HMWP_Classes_Tools::getValue('loginpress_code') && HMWP_Classes_Tools::isPluginActive('loginpress/loginpress.php')) {
                        add_filter('hmwp_process_hide_urls', '__return_false');
                    }

                    //If there is a process that need to access the wp-admin
                    if (get_transient('hmwp_disable_hide_urls')) {
                        add_filter('hmwp_process_hide_urls', '__return_false');
                    }
                }
            }, 10
        );

        //Compatibility with WPML plugin
        if (HMWP_Classes_Tools::isPluginActive('sitepress-multilingual-cms/sitepress.php') ) {
            //WPML checks the HTTP_REFERER based on wp-admin and not the custom admin path
            if (isset($_SERVER['HTTP_REFERER']) ) {
                $_SERVER['HTTP_REFERER'] = HMWP_Classes_ObjController::getClass('HMWP_Models_Files')->getOriginalUrl($_SERVER['HTTP_REFERER']);
            }
        }

        //Compatibility with iThemes security plugin
        if (HMWP_Classes_Tools::isPluginActive('ithemes-security-pro/ithemes-security-pro.php') 
            || HMWP_Classes_Tools::isPluginActive('better-wp-security/better-wp-security.php') 
        ) {
            $settings = get_option('itsec-storage');
            if (isset($settings['hide-backend']['enabled']) && $settings['hide-backend']['enabled'] ) {
                if (isset($settings['hide-backend']['slug']) && $settings['hide-backend']['slug'] <> '' ) {
                    defined('HMWP_DEFAULT_LOGIN') || define('HMWP_DEFAULT_LOGIN', $settings['hide-backend']['slug']);
                    HMWP_Classes_Tools::$options['hmwp_login_url'] = HMWP_Classes_Tools::$default['hmwp_login_url'];
                }
            }
        }

        //Compatibility with iThemes security plugin
        if (HMWP_Classes_Tools::isPluginActive('wps-hide-login/wps-hide-login.php') ) {
            if ($whl_page = get_option('whl_page') ) {
                defined('HMWP_DEFAULT_LOGIN') || define('HMWP_DEFAULT_LOGIN', $whl_page);
                HMWP_Classes_Tools::$options['hmwp_login_url'] = HMWP_Classes_Tools::$default['hmwp_login_url'];
            }
        }

        //Add Compatibility with PPress plugin
        //Load the post from Ppress for the login page
        if (HMWP_Classes_Tools::isPluginActive('ppress/profilepress.php') ) {

            if ('logout' <> HMWP_Classes_Tools::getValue('action') ) {

                add_action(
                    'hmwp_login_init', function () {
                        //Add compatibility with PPress plugin
                        $data = get_option('pp_settings_data');
                        if (class_exists('WP_Query') && isset($data['set_login_url']) && (int)$data['set_login_url'] > 0 ) {
                            $query = new WP_Query(array('p' => $data['set_login_url'], 'post_type' => 'any'));
                            if ($query->have_posts() ) {
                                $query->the_post();
                                get_header();
                                the_content();
                                get_footer();
                            }
                            exit();
                        }

                    } 
                );

            }

        }

        //Add compatibility with WP Defender plugin
        if (HMWP_Classes_Tools::isPluginActive('wp-defender/wp-defender.php') ) {

            add_action(
                'login_form_defender-verify-otp', function () {

                    if (!isset($_SERVER['REQUEST_METHOD']) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
                        return;
                    }

                    $_POST['_wpnonce'] = wp_create_nonce('verify_otp');

                }, 9 
            );

        }

        //Add compatibility with Wordfence to not load the Bruteforce when 2FA is active
        if(HMWP_Classes_Tools::getOption('hmwp_bruteforce') && HMWP_Classes_Tools::getOption('brute_use_captcha_v3')
            && HMWP_Classes_Tools::isPluginActive('wordfence/wordfence.php')
        ) {

            add_filter('hmwp_option_brute_use_captcha_v3', '__return_false');
        }

        //Compatibility with Smart Slider
        if (HMWP_Classes_Tools::isPluginActive('smart-slider-3/smart-slider-3.php') || HMWP_Classes_Tools::isPluginActive('nextend-smart-slider3-pro/nextend-smart-slider3-pro.php') ) {
            add_filter('hmwp_priority_buffer', '__return_true');
        }

        //Compatibility with Fluent CRM - tested 11162021
        if (HMWP_Classes_Tools::isPluginActive('fluent-crm/fluent-crm.php') || HMWP_Classes_Tools::isPluginActive('fluent-smtp/fluent-smtp.php') ) {
            add_filter('hmwp_option_hmwp_hideajax_paths', '__return_false');
        }
    }

    /**
     * Find Replace cache plguins
     * Stop Buffer from loading
     *
     * @param  $content
     * @return mixed
     * @throws Exception
     */
    public function findReplaceCache( $content)
    {
        //if called from cache plugins or hooks, stop the buffer replace
        add_filter('hmwp_process_buffer', '__return_false');

        return HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace($content);

    }

    /**
     * Echo the changed HTML buffer
     * @throws Exception
     */
    public function findReplaceBuffer()
    {
        //Force to change the URL for xml content types
        $buffer = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace(ob_get_contents());

        ob_end_clean();
        echo $buffer;
    }

    /**
     * Check other plugins and set compatibility settings
     *
     * @throws Exception
     */
    public function checkBuildersCompatibility()
    {

        //Check the compatibility with builders
        //Don't load when on builder editor
        //Compatibility with Oxygen Plugin, Elementor, Thrive and more, Yellow Pencil, Wp Bakery
        if (function_exists('is_user_logged_in') && is_user_logged_in() ) {
            $builder_paramas = array(
                'fl_builder', //Beaver Builder
                'fb-edit', //Fusion Builder
                'builder', //Fusion Builder
                'vc_action', //WP Bakery
                'vc_editable', //WP Bakery
                'vcv-action', //WP Bakery
                'et_fb', //Divi
                'ct_builder', //Oxygen
                'tve', //Thrive
                'preview', //Blockeditor & Gutenberg
                'elementor-preview', //Elementor
                'uxb_iframe',
                'wyp_page_type', //Yellowpencil plugin
                'wyp_mode',//Yellowpencil plugin
                'brizy-edit-iframe',//Brizy plugin
                'bricks',//Bricks plugin
                'zionbuilder-preview',//Zion Builder plugin
                'customize_theme',//WordPress Customize
            );

            foreach ( $builder_paramas as $param ) {
                if (HMWP_Classes_Tools::getIsset($param) ) {
                    //Stop Hide My WP Ghost from loading while on editor
                    add_filter('hmwp_start_buffer', '__return_false');
                    add_filter('hmwp_process_buffer', '__return_false');
                    add_filter('hmwp_process_hide_disable', '__return_false');
                    add_filter('hmwp_process_find_replace', '__return_false');
                    return;
                }
            }
        }

    }

	/**
	 * Check if there are whitelisted IPs for accessing the hidden paths
	 * @return void
	 */
	public function checkWhitelistIPs(){
		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] <> '' ) {
			$ips = array();
			if (HMWP_Classes_Tools::getOption('whitelist_ip')) {
				$ips = json_decode(HMWP_Classes_Tools::getOption('whitelist_ip'), true);
			}

			if(!empty($ips) && in_array($_SERVER['REMOTE_ADDR'], $ips)){
				add_filter('hmwp_process_hide_urls', '__return_false');
			}
		}
	}

    /**
     * Compatibility with All In On Security plugin
     *
     * @param string $content
     *
     * @throws Exception
     */
    public function aioSecurityMaintenance( $content )
    {
        if (defined('AIO_WP_SECURITY_PATH') ) {
            if (empty($content) ) {
                nocache_headers();
                header("HTTP/1.0 503 Service Unavailable");
                remove_action('wp_head', 'head_addons', 7);

                ob_start();
                $template = apply_filters('aiowps_site_lockout_template_include', AIO_WP_SECURITY_PATH . '/other-includes/wp-security-visitor-lockout-page.php');
                include_once $template;
                $output = ob_get_clean();

                echo HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace($output);
            } else {
                echo $content;
            }

            exit();
        }
    }

    /**
     * Check if the cache plugins are loaded and have cached files
     *
     * @throws Exception
     */
    public function checkCacheFiles()
    {
        $changed = false;

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();
        $content_dir = $wp_filesystem->wp_content_dir();

        //Change the paths in the cached css
        if (HMWP_Classes_Tools::isPluginActive('elementor/elementor.php') ) {
            //Set the cache directory for this plugin
            $path = $content_dir . HMWP_Classes_Tools::$default['hmwp_upload_url'] . '/elementor/css/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                //mark as cache changed
                $changed = true;
            }
        }

        //Change the paths in the cached css
        if (HMWP_Classes_Tools::isPluginActive('fusion-builder/fusion-builder.php') ) {
            //Set the cache directory for this plugin
            $path = $content_dir . HMWP_Classes_Tools::$default['hmwp_upload_url'] . '/fusion-styles/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //mark as cache changed
                $changed = true;
            }
        }

        //Change the paths in the cached css
        if (HMWP_Classes_Tools::isPluginActive('beaver-builder-lite-version/fl-builder.php') 
            || HMWP_Classes_Tools::isPluginActive('beaver-builder/fl-builder.php') 
        ) {
            //Set the cache directory for this plugin
            $path = $content_dir . HMWP_Classes_Tools::$default['hmwp_upload_url'] . '/bb-plugin/cache/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                //mark as cache changed
                $changed = true;
            }
        }

        //Change the paths in the cached css
        if (HMWP_Classes_Tools::isPluginActive('wp-super-cache/wp-cache.php') ) {

            //Initialize WordPress Filesystem
            $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

            $wp_cache_config_file = $content_dir . 'wp-cache-config.php';

            if ($wp_filesystem->exists($wp_cache_config_file) ) {
                include $wp_cache_config_file;
            }

            //Set the cache directory for this plugin
            if (isset($cache_path) ) {
                $path = $cache_path;
            } else {
                $path = $content_dir . 'cache';
            }

            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                //mark as cache changed
                $changed = true;
            }
        }

        //Change the paths in the cached css
        if (HMWP_Classes_Tools::isPluginActive('litespeed-cache/litespeed-cache.php') ) {
            //Set the cache directory for this plugin
            $path = $content_dir . 'litespeed/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                //mark as cache changed
                $changed = true;
            }
        }

        //Change the paths in the cached css
        if (HMWP_Classes_Tools::isPluginActive('comet-cache/comet-cache.php') ) {

            //Set the cache directory for this plugin
            $path = false;
            if ($options = get_option('comet_cache_options') ) {
                if (isset($options['base_dir']) ) {
                    $path = $content_dir . trim($options['base_dir'], '/') . '/';
                }
            }

            if (!$path ) {
                $path = $content_dir . 'cache/';
            }

            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                //mark as cache changed
                $changed = true;
            }
        }

        if (HMWP_Classes_Tools::isPluginActive('hummingbird-performance/wp-hummingbird.php') ) {
            //Set the cache directory for this plugin
            $path = $content_dir . 'wphb-cache/';

            if ($options = get_option('wphb_settings') ) {
                if (isset($options['minify']['file_path']) ) {
                    $path = $wp_filesystem->abspath()  . trim($options['minify']['file_path'], '/') . '/';
                }
            }

            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                //mark as cache changed
                $changed = true;
            }
        }

        if (HMWP_Classes_Tools::isPluginActive('hyper-cache/plugin.php') ) {
            //Set the cache directory for this plugin
            if (defined('HYPER_CACHE_FOLDER') ) {
                $path = HYPER_CACHE_FOLDER;
            } else {
                $path = $content_dir . 'cache/';
            }

            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();
                //change the paths in html
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInHTML();

                //mark as cache changed
                $changed = true;
            }
        }

        //For WP-Rocket
        if (HMWP_Classes_Tools::isPluginActive('wp-rocket/wp-rocket.php') ) {
            if (function_exists('get_rocket_option') ) {
                $concatenate = get_rocket_option('minify_concatenate_css');

                if ($concatenate ) {
                    //Set the cache directory for this plugin
                    $path = $content_dir . 'cache/min/';
                    if (function_exists('get_current_blog_id') ) {
                        $path .= get_current_blog_id() . '/';
                    }

                    if($wp_filesystem->is_dir($path)) {
                        HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                        //change the paths in css
                        HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                        //change the paths in js
                        HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                        //mark as cache changed
                        $changed = true;
                    }
                }
            }


        }

        //For Autoptimizer
        if (HMWP_Classes_Tools::isPluginActive('autoptimize/autoptimize.php') ) {
            //Set the cache directory for this plugin
            $path = $content_dir . 'cache/autoptimize/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();
                //mark as cache changed
                $changed = true;
            }
        }

        //For bb-plugin
        if (HMWP_Classes_Tools::isPluginActive('beaver-builder-lite-version/fl-builder.php') ) {
            //Set the cache directory for this plugin
            $path = $content_dir . 'uploads/bb-plugin/cache/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //change the paths in css
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();
                //mark as cache changed
                $changed = true;
            }
        }

        //For WP Fastest Cache
        if (HMWP_Classes_Tools::isPluginActive('wp-fastest-cache/wpFastestCache.php') ) {
            //Set the cache directory for this plugin
            $path = $content_dir . 'cache/wpfc-minified/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //Change the paths in cache
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();
                //Change the paths in html
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInHTML();
            }

            $path = $content_dir . 'cache/all/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //Change the paths in cache
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInHTML();

                //mark as cache changed
                $changed = true;
            }
        }

        //For Siteground Cache
        if (HMWP_Classes_Tools::isPluginActive('sg-cachepress/sg-cachepress.php') ) {
            //Set the cache directory for this plugin
            $path = $content_dir . HMWP_Classes_Tools::$default['hmwp_upload_url'] . '/siteground-optimizer-assets/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //Change the paths in cache
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                //mark as cache changed
                $changed = true;
            }
        }

        //For JCH Optimize Cache
        if (HMWP_Classes_Tools::isPluginActive('jch-optimize/jch-optimize.php') ) {
            //Change the paths in css
            $path = $content_dir . 'cache/jch-optimize/css/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();

                //mark as cache changed
                $changed = true;
            }

            //change the paths in js
            $path = $content_dir . 'cache/jch-optimize/js/';
            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                //mark as cache changed
                $changed = true;
            }

        }

        //IF none of these plugins are installed. Search whole directory.
        if (!$changed || HMWP_Classes_Tools::getOption('hmwp_change_in_cache_directory') <> '') {
            //Set the cache directory for this plugin
            if(HMWP_Classes_Tools::getOption('hmwp_change_in_cache_directory') <> '') {
                $path = $content_dir . trim(HMWP_Classes_Tools::getOption('hmwp_change_in_cache_directory'), '/') . '/';
            }else{
                $path = $content_dir . 'cache/';
            }

            if($wp_filesystem->is_dir($path)) {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->setCachePath($path);

                //if other cache plugins are installed
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInCss();
                //change the paths in js
                HMWP_Classes_ObjController::getClass('HMWP_Models_Cache')->changePathsInJs();

                //mark as cache changed
                $changed = true;
            }
        }

        if($changed && isset($path)) {
            //For debugging
            do_action('hmwp_debug_cache', date('Y-m-d H:i:s') . PHP_EOL . $path);
        }

    }

    /**
     * Get all alert messages
     *
     * @throws Exception
     */
    public static function getAlerts()
    {

        //First thing you need to do
        $page = HMWP_Classes_Tools::getValue('page');
        if (HMWP_Classes_Tools::getOption('hmwp_mode') == 'default' && $page <> 'hmwp_permalinks') {
            HMWP_Classes_Error::setError(sprintf(esc_html__('First, you need to activate the %sLite Mode%s in %s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_permalinks').'"><strong>', '</strong></a>', '<strong>'.HMWP_Classes_Tools::getOption('hmwp_plugin_name').'</strong>'));
        }

        //is CDN plugin installed
        if (is_admin() || is_network_admin() ) {
            if (HMWP_Classes_Tools::isPluginActive('cdn-enabler/cdn-enabler.php') ) {
                if (HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default' ) {
                    if ($cdn_enabler = get_option('cdn_enabler') ) {
                        if (isset($cdn_enabler['dirs']) ) {
                            $dirs = explode(',', $cdn_enabler['dirs']);
                            if (!empty($dirs) 
                                && !in_array(HMWP_Classes_Tools::getOption('hmwp_wp-content_url'), $dirs) 
                                && !in_array(HMWP_Classes_Tools::getOption('hmwp_wp-includes_url'), $dirs)
                            ) {
                                HMWP_Classes_Error::setError(sprintf(esc_html__('CDN Enabled detected. Please include %s and %s paths in CDN Enabler Settings', 'hide-my-wp'), '<strong>' . HMWP_Classes_Tools::getOption('hmwp_wp-content_url') . '</strong>', '<strong>' . HMWP_Classes_Tools::getOption('hmwp_wp-includes_url') . '</strong>'));
                            }
                        }
                    }
                }

                if (isset($_SERVER['REQUEST_URI']) && admin_url('options-general.php?page=cdn_enabler', 'relative') == $_SERVER['REQUEST_URI'] ) {
                    HMWP_Classes_Error::setError(sprintf(esc_html__("CDN Enabler detected! Learn how to configure it with %s %sClick here%s", 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name'), '<a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/hide-my-wp-and-cdn-enabler/" target="_blank">', '</a>'));
                }
            }

            //Compatibility with WP Cache plugin for CDN list
            if (HMWP_Classes_Tools::isPluginActive('wp-super-cache/wp-cache.php') ) {
                if (get_option('ossdl_off_cdn_url') <> '' && get_option('ossdl_off_cdn_url') <> home_url() ) {
                    $dirs = explode(',', get_option('ossdl_off_include_dirs'));
                    if (!empty($dirs) 
                        && !in_array(HMWP_Classes_Tools::getOption('hmwp_wp-content_url'), $dirs) 
                        && !in_array(HMWP_Classes_Tools::getOption('hmwp_wp-includes_url'), $dirs)
                    ) {
                        HMWP_Classes_Error::setError(sprintf(esc_html__('WP Super Cache CDN detected. Please include %s and %s paths in WP Super Cache > CDN > Include directories', 'hide-my-wp'), '<strong>' . HMWP_Classes_Tools::getOption('hmwp_wp-content_url') . '</strong>', '<strong>' . HMWP_Classes_Tools::getOption('hmwp_wp-includes_url') . '</strong>'));
                    }
                }
            }

            //Admin Ajax alert for Affiliate Pro plugin
	        //indeed-membership-pro%2Findeed-membership-pro
            if (HMWP_Classes_Tools::isPluginActive('indeed-affiliate-pro/indeed-affiliate-pro.php') ) {
                if (HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url') <>  HMWP_Classes_Tools::$default['hmwp_admin-ajax_url'] ) {
                    HMWP_Classes_Error::setError(sprintf(esc_html__("Ultimate Affiliate Pro detected. The plugin doesn't support custom %s paths as it doesn't use WordPress functions to call the Ajax URL", 'hide-my-wp'), '<strong>admin-ajax.php</strong>'));
                }
            }

	        if (HMWP_Classes_Tools::isPluginActive('indeed-membership-pro/indeed-membership-pro.php')) {
		        if (HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url') <>  HMWP_Classes_Tools::$default['hmwp_admin-ajax_url'] ) {
			        HMWP_Classes_Error::setError(sprintf(esc_html__("Indeed Ultimate Membership Pro detected. The plugin doesn't support custom %s paths as it doesn't use WordPress functions to call the Ajax URL", 'hide-my-wp'), '<strong>admin-ajax.php</strong>'));
		        }
	        }

	        //Mor Rewrite is not installed
            if (HMWP_Classes_Tools::isApache() && !HMWP_Classes_Tools::isModeRewrite() ) {
                HMWP_Classes_Error::setError(sprintf(esc_html__('%s does not work without mode_rewrite. Please activate the rewrite module in Apache. %sMore details%s', 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name'), '<a href="https://tecadmin.net/enable-apache-mod-rewrite-module-in-ubuntu-linuxmint/" target="_blank">', '</a>'));
            }

            //IIS server and no Rewrite Permalinks installed
            if (HMWP_Classes_Tools::isIIS() && HMWP_Classes_Tools::isPHPPermalink() ) {
                HMWP_Classes_Error::setError(sprintf(esc_html__('You need to activate the URL Rewrite for IIS to be able to change the permalink structure to friendly URL (without index.php). %sMore details%s', 'hide-my-wp'), '<a href="https://www.iis.net/downloads/microsoft/url-rewrite" target="_blank">', '</a>'));
            } elseif (HMWP_Classes_Tools::isPHPPermalink() ) {
                HMWP_Classes_Error::setError(esc_html__('You need to set the permalink structure to friendly URL (without index.php).', 'hide-my-wp'));
            }

            //Inmotion server detected
            if (HMWP_Classes_Tools::isInmotion() && HMWP_Classes_Tools::isNginx()) {
                HMWP_Classes_Error::setError(sprintf(esc_html__('Inmotion detected. %sPlease read how to make the plugin compatible with Inmotion Nginx Cache%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/hide-my-wp-pro-compatible-with-inmotion-wordpress-hosting/" target="_blank">', '</a>'));
            }

            if (HMWP_Classes_Tools::isAWS() ) {
                HMWP_Classes_Error::setError(sprintf(esc_html__('Bitnami detected. %sPlease read how to make the plugin compatible with AWS hosting%s', 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/how-to-set-hide-my-wp-for-bitnami-servers/" target="_blank">', '</a>'));
            }

            //The login path is changed by other plugins and may affect the functionality
            if (HMWP_Classes_Tools::$default['hmwp_login_url'] == HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
                if (strpos(site_url('wp-login.php'), HMWP_Classes_Tools::$default['hmwp_login_url']) === false ) {
                    defined('HMWP_DEFAULT_LOGIN') || define('HMWP_DEFAULT_LOGIN', site_url('wp-login.php'));
                }
            }

	        if (HMWP_Classes_Tools::isThemeActive('Avada') ) {
		        if ((HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default')) {
			        if (defined('FUSION_LIBRARY_URL') && strpos(FUSION_LIBRARY_URL, 'wp-content') !== false && HMWP_Classes_Tools::getOption('hmwp_themes_url')  <> HMWP_Classes_Tools::$default['hmwp_themes_url'] ) {
				        $avadaPath = false;
				        $themes = HMWP_Classes_Tools::getOption('hmwp_themes');

				        foreach ($themes['from'] as $index => $theme){
					        if(strpos($theme,'Avada') !== false){
						        $avadaPath = trim($themes['to'][$index] , '/');
					        }
				        }

				        if($avadaPath && $avadaPath <> 'Avada'){
					        HMWP_Classes_Error::setError(sprintf(esc_html__('To hide the Avada library, please add the Avada FUSION_LIBRARY_URL in wp-config.php file after $table_prefix line: %s', 'hide-my-wp'), '<br /><strong>define(\'FUSION_LIBRARY_URL\',\'' . site_url(HMWP_Classes_Tools::getOption('hmwp_themes_url')) . '/'.$avadaPath.'/includes/lib\');</strong>'));
				        }
			        }
		        }
	        }

	        //The admin URL is already changed by other plugins and may affect the functionality
            if (!HMW_RULES_IN_CONFIG ) {
                HMWP_Classes_Error::setError(sprintf(esc_html__('%s rules are not saved in the config file and this may affect the website loading speed.', 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name')));
                defined('HMWP_DEFAULT_ADMIN') || define('HMWP_DEFAULT_ADMIN', HMWP_Classes_Tools::$default['hmwp_admin_url']);
            } elseif (HMWP_Classes_Tools::$default['hmwp_admin_url'] == HMWP_Classes_Tools::getOption('hmwp_admin_url') ) {
                if (strpos(admin_url(), HMWP_Classes_Tools::$default['hmwp_admin_url']) === false ) {
                    defined('HMWP_DEFAULT_ADMIN') || define('HMWP_DEFAULT_ADMIN', admin_url());
                }
            }

            //Show the option to change in cache files
            if (HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default' && !HMWP_Classes_Tools::getOption('test_frontend')  &&  HMWP_Classes_Tools::isCachePlugin() && !HMWP_Classes_Tools::getOption('hmwp_change_in_cache')) {
                HMWP_Classes_Error::setError(sprintf(esc_html__("To change the paths in the cached files, switch on %s Change Paths in Cached Files%s", 'hide-my-wp'), '<strong><a href="'. HMWP_Classes_Tools::getOption('hmwp_plugin_website') .'/kb/activate-security-tweaks/#change_paths_cached_files" target="_blank">', '</a></strong>'));
            }

            if (HMWP_Classes_Tools::isGodaddy() ) {
                HMWP_Classes_Error::setError(sprintf(esc_html__("Godaddy detected! To avoid CSS errors, make sure you switch off the CDN from %s", 'hide-my-wp'), '<strong>' . '<a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/how-to-use-hide-my-wp-with-godaddy/" target="_blank"> Godaddy > Managed WordPress > Overview</a>' . '</strong>'));
            }

            if (HMWP_Classes_Tools::isPluginActive('bulletproof-security/bulletproof-security.php') ) {
                HMWP_Classes_Error::setError(sprintf(esc_html__("BulletProof plugin! Make sure you save the settings in %s after activating Root Folder BulletProof Mode in BulletProof plugin.", 'hide-my-wp'), HMWP_Classes_Tools::getOption('hmwp_plugin_name')));
            }

            if (HMWP_Classes_Tools::isPluginActive('worker/init.php') && !HMWP_Classes_Tools::getOption('hmwp_firstload')  ) {
                HMWP_Classes_Error::setError(sprintf(esc_html__("Activate the compatibility with Manage WP plugin to be able to connect to your dashboard directly from managewp.com. %s click here %s", 'hide-my-wp'), '<a href="'.HMWP_Classes_Tools::getSettingsUrl('hmwp_advanced#tab=compatibility', true).'" >', '</a>'));
            }

            //Check if the rules are working as expected
            $mappings = HMWP_Classes_Tools::getOption('file_mappings');
            if (!empty($mappings) ) {
                $restoreLink = '<br /><a href="'.add_query_arg(array('hmwp_nonce' => wp_create_nonce('hmwp_ignore_errors'), 'action' => 'hmwp_ignore_errors')) .'" class="btn btn-default btn-sm mt-3" />' . esc_html__("Close Error", 'hide-my-wp'). '</a>';
                HMWP_Classes_Error::setError(sprintf(esc_html__('Attention! Some URLs passed through the config file rules and were loaded through WordPress rewrite which may slow down your website. %s Please follow this tutorial to fix the issue: %s', 'hide-my-wp'), '<br /><br />' . join('<br />', $mappings) . '<br /><br />', '<a href="'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/kb/when-the-website-loads-slower-with-hide-my-wp-ghost/" target="_blank" class="text-warning">'.HMWP_Classes_Tools::getOption('hmwp_plugin_website').'/kb/when-the-website-loads-slower-with-hide-my-wp-ghost/</a> ' . $restoreLink), 'text-white bg-danger');
            }

        }

    }

    /**
     * Fix WP Rocket reject URL
     *
     * @param $uri
     *
     * @return array
     */
    public function rocket_reject_url( $uri )
    {
        if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
            $path = parse_url(home_url(), PHP_URL_PATH);
            $uri[] = ($path <> '/' ? $path . '/' : $path) . HMWP_Classes_Tools::getOption('hmwp_login_url');
        }

        return $uri;
    }

    /**
     * Create the WP-Rocket Burst Mapping
     *
     * @throws Exception
     */
    public function rocket_burst_mapping()
    {
        //Add the URL mapping for wp-rocket plugin
        if (HMWP_Classes_Tools::isPluginActive('wp-rocket/wp-rocket.php') ) {
            if (HMWP_Classes_Tools::$default['hmwp_wp-content_url'] <> HMWP_Classes_Tools::getOption('hmwp_wp-content_url') 
                || HMWP_Classes_Tools::$default['hmwp_wp-includes_url'] <> HMWP_Classes_Tools::getOption('hmwp_wp-includes_url') 
            ) {
                if (defined('WP_ROCKET_CACHE_BUSTING_URL') ) {
                    $hmwp_url_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_url_mapping'), true);

                    //if no mapping is set allready
                    if (!isset($hmwp_url_mapping['from']) ) {
                        $blog_ids = array();
                        if (HMWP_Classes_Tools::isMultisiteWithPath() ) {
                            global $wpdb;
                            $blogs = $wpdb->get_results( "SELECT blog_id FROM " . $wpdb->blogs );
                            foreach ( $blogs as $blog ) {
                                $blog_ids[] = $blog->blog_id;
                            }
                        } else {
                            $blog_ids[] = get_current_blog_id();
                        }

                        $home_root = parse_url(home_url());
                        if (isset($home_root['path']) ) {
                            $home_root = trailingslashit($home_root['path']);
                        } else {
                            $home_root = '/';
                        }

                        $busting_url = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace_url(WP_ROCKET_CACHE_BUSTING_URL);
                        if ($busting_url = HMWP_Classes_Tools::getRelativePath($busting_url) ) {
                            foreach ( $blog_ids as $blog_id ) {
                                //mapp the wp-rocket busting wp-content
                                if (HMWP_Classes_Tools::$default['hmwp_wp-content_url'] <> HMWP_Classes_Tools::getOption('hmwp_wp-content_url') ) {
                                    $hmwp_url_mapping['from'][] = '/' . $busting_url . '/' . $blog_id . $home_root . HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/';
                                    $hmwp_url_mapping['to'][] = '/' . $busting_url . '/' . $blog_id . '/' . HMWP_Classes_Tools::getOption('hmwp_wp-content_url') . '/';
                                }

                                //mapp the wp-rocket busting wp-includes
                                if (HMWP_Classes_Tools::$default['hmwp_wp-includes_url'] <> HMWP_Classes_Tools::getOption('hmwp_wp-includes_url') ) {
                                    $hmwp_url_mapping['from'][] = '/' . $busting_url . '/' . $blog_id . $home_root . HMWP_Classes_Tools::$default['hmwp_wp-includes_url'] . '/';
                                    $hmwp_url_mapping['to'][] = '/' . $busting_url . '/' . $blog_id . '/' . HMWP_Classes_Tools::getOption('hmwp_wp-includes_url') . '/';
                                }
                            }
                        }

                        HMWP_Classes_Tools::saveOptions('hmwp_url_mapping', json_encode($hmwp_url_mapping));
                    }
                }
            }
        }
    }

    /**
     * Include CDNs if found
     *
     * @return array|false
     */
    public function findCDNServers()
    {
        $domains = array();

        //WP Rocket CDN Integration
        if (HMWP_Classes_Tools::isPluginActive('wp-rocket/wp-rocket.php') && function_exists('get_rocket_option') ) {
            $cnames = get_rocket_option('cdn_cnames', array());
            foreach ($cnames as $_urls ) {

                $_urls = explode(',', $_urls);
                $_urls = array_map('trim', $_urls);

                foreach ( $_urls as $url ) {
                    $domains[] = $url;
                }
            }
        }

        //CDN Enabler Integration
        if (HMWP_Classes_Tools::isPluginActive('cdn-enabler/cdn-enabler.php') ) {
            if ($cdn_enabler = get_option('cdn_enabler') ) {
                if (isset($cdn_enabler['url']) ) {
                    $domains[] = $cdn_enabler['url'];
                }
            }
        }

        //Power Cache CDN integration
        if (HMWP_Classes_Tools::isPluginActive('powered-cache/powered-cache.php') ) {
            global $powered_cache_options;
            if (isset($powered_cache_options['cdn_hostname']) ) {
                $hostnames = $powered_cache_options['cdn_hostname'];
                if (!empty($hostnames) ) {
                    foreach ( $hostnames as $host ) {
                        if (!empty($host) ) {
                            $domains[] = $host;
                        }
                    }
                }
            }
        }

        //Wp Cache CDN integration
        if (HMWP_Classes_Tools::isPluginActive('wp-super-cache/wp-cache.php') ) {
            if (get_option('ossdl_off_cdn_url') <> '' && get_option('ossdl_off_cdn_url') <> home_url() ) {
                $domains[] = get_option('ossdl_off_cdn_url');
            }
        }

        //JCH Optimize CDN integration
        if (HMWP_Classes_Tools::isPluginActive('jch-optimize/jch-optimize.php') ) {
            if ($jch = get_option('jch_options') ) {
                if(is_array($jch)) {
                    if (isset($jch['cookielessdomain_enable']) && $jch['cookielessdomain_enable'] 
                        && isset($jch['cookielessdomain']) && $jch['cookielessdomain'] <> ''
                    ) {
                        $domains[] = $jch['cookielessdomain'];
                    }
                }
            }
        }

        //get Hide My WP CDN list
        $hmwp_cdn_urls = json_decode(HMWP_Classes_Tools::getOption('hmwp_cdn_urls'), true);
        if (!empty($hmwp_cdn_urls) ) {
            foreach ( $hmwp_cdn_urls as $url ) {
                $domains[] = $url;
            }
        }

        //Hyper Cache CDN integration
        if (HMWP_Classes_Tools::isPluginActive('hyper-cache/plugin.php') ) {
            if ($cdn = get_option('hyper-cache') ) {
                if (isset($cdn['cdn_enabled']) && $cdn['cdn_enabled'] && isset($cdn['cdn_url']) && $cdn['cdn_url']  ) {
                    $domains[] = $cdn['cdn_url'];
                }
            }
        }

        //Bunny CDN integration
        if (HMWP_Classes_Tools::isPluginActive('bunnycdn/bunnycdn.php') ) {
            if ($bunnycdn = get_option('bunnycdn') ) {
                if (isset($bunnycdn['cdn_domain_name']) && $bunnycdn['cdn_domain_name']  ) {
                    $domains[] = $bunnycdn['cdn_domain_name'];
                }
            }
        }


        if (!empty($domains) ) {
            return array_unique($domains);
        }

        return false;
    }

    /**
     * Fix compatibility with WooGC plugin
     *
     * @param $buffer
     *
     * @return mixed
     */
    public function fix_woogc_shutdown( $buffer )
    {
        global $blog_id, $woocommerce, $WooGC;

        if (!class_exists('WooGC') ) {
            return $buffer;
        }

        if (!is_object($woocommerce->cart) ) {
            return $buffer;
        }

        if (class_exists('WooGC') ) {
            if ($WooGC && !$WooGC instanceof WooGC ) {
                return $buffer;
            }
        }

        $options = $WooGC->functions->get_options();
        $blog_details = get_blog_details($blog_id);

        //replace any checkout links
        if (!empty($options['cart_checkout_location']) && $options['cart_checkout_location'] != $blog_id ) {
            $checkout_url = $woocommerce->cart->get_checkout_url();
            $checkout_url = str_replace(array('http:', 'https:'), "", $checkout_url);
            $checkout_url = trailingslashit($checkout_url);

            $buffer = str_replace($blog_details->domain . "/checkout/", $checkout_url, $buffer);

        }

        return $buffer;
    }

    /**
     * Add rules to be compatible with Simple SSL plugins
     */
    public function checkSimpleSSLRewrites()
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        try {
            $options = get_option('rlrsssl_options');

            if (isset($options['htaccess_redirect']) && $options['htaccess_redirect'] ) {
                $config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();
                $htaccess = $wp_filesystem->get_contents($config_file);
                preg_match("/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s", $htaccess, $match);

                if (isset($match[0]) && !empty($match[0]) ) {
                    $htaccess = preg_replace("/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s", "", $htaccess);
                    $htaccess = $match[0] . PHP_EOL . $htaccess;
                    $htaccess = preg_replace("/\n+/", "\n", $htaccess);
                    $wp_filesystem->put_contents($config_file, $htaccess);
                }
            }
        } catch ( Exception $e ) {
        }
    }

    /************************************************************
     * 
     * Must Use Plugin (needed for Manage WP and other cache plugins) 
     */

    /**
     * Add the Must-Use plugin to make sure is loading for the custom wp-admin path every time
     */
    public function addMUPlugin()
    {
        try {
            $this->registerMUPlugin('0-hidemywp.php', $this->buildLoaderContent('hide-my-wp/index.php'));
        } catch ( Exception $e ) {
        }
    }

    /**
     * Remove the Must-Use plugin on deactivation
     */
    public function deleteMUPlugin()
    {
        try {
            $this->deregisterMUPlugin('0-hidemywp.php');
        } catch ( Exception $e ) {
        }
    }

    /**
     * The MU plugin content
     *
     * @param  $pluginBasename
     * @return string
     */
    public function buildLoaderContent( $pluginBasename )
    {
        return "<?php
        /*
        Plugin Name: HMWP Ghost Loader
        Description: This is automatically generated by the HMWP plugin to increase performance and reliability. It is automatically disabled when disabling the main plugin.
        */
        
        if (function_exists('untrailingslashit') && defined('WP_PLUGIN_DIR') && @file_exists(untrailingslashit(WP_PLUGIN_DIR).'/$pluginBasename')) {
            if (in_array('$pluginBasename', (array) get_option('active_plugins')) ) {
                include_once untrailingslashit(WP_PLUGIN_DIR).'/$pluginBasename';
            }
        }";

    }

    /**
     * Add the MU file
     *
     * @param $loaderName
     * @param $loaderContent
     */
    public function registerMUPlugin( $loaderName, $loaderContent )
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        $mustUsePluginDir = rtrim(WPMU_PLUGIN_DIR, '/');
        $loaderPath = $mustUsePluginDir . '/' . $loaderName;

        if ($wp_filesystem->exists($loaderPath) && md5($loaderContent) === md5_file($loaderPath) ) {
            return;
        }

        if (!$wp_filesystem->is_dir($mustUsePluginDir) ) {
            $wp_filesystem->mkdir($mustUsePluginDir);
        }

        if ($wp_filesystem->is_writable($mustUsePluginDir) ) {
            $wp_filesystem->put_contents($loaderPath, $loaderContent);
        }

    }

    /**
     * Delete the MU file
     *
     * @param $loaderName
     */
    public function deregisterMUPlugin( $loaderName )
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        $mustUsePluginDir = rtrim(WPMU_PLUGIN_DIR, '/');
        $loaderPath = $mustUsePluginDir . '/' . $loaderName;

        if (!$wp_filesystem->exists($loaderPath) ) {
            return;
        }

        $wp_filesystem->delete($loaderPath);
    }


    /**
     * Conpatibility with Confirm Email from AppThemes
     *
     * call the appthemes_confirm_email_template_redirect
     * for custom login paths
     */
    public function checkAppThemesConfirmEmail()
    {

        if (HMWP_Classes_Tools::getIsset('action') ) {
            if (function_exists('appthemes_confirm_email_template_redirect') ) {
                appthemes_confirm_email_template_redirect();
            }
        }

    }

    /**
     * Compatibility with W3 Total Cache Lazy Load
     *
     * @param  $buffer
     * @return string|string[]|null
     * @throws Exception
     */
    public function embedW3TotalCacheLazyLoadscript( $buffer )
    {
        $js_url = plugins_url('pub/js/lazyload.min.js', W3TC_FILE);
        $js_url = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace_url($js_url);

        $fireEvent = 'function(t){var e;try{e=new CustomEvent("w3tc_lazyload_loaded",{detail:{e:t}})}catch(a){(e=document.createEvent("CustomEvent")).initCustomEvent("w3tc_lazyload_loaded",!1,!1,{e:t})}window.dispatchEvent(e)}';
        $config = '{elements_selector:".lazy",callback_loaded:' . $fireEvent . '}';

        $on_initialized_javascript = apply_filters('w3tc_lazyload_on_initialized_javascript', '');

        $on_initialized_javascript_wrapped = '';
        if (!empty($on_initialized_javascript) ) {
            // LazyLoad::Initialized fired just before making LazyLoad global
            // so next execution cycle have it
            $on_initialized_javascript_wrapped =
                'window.addEventListener("LazyLoad::Initialized", function(){' .
                'setTimeout(function() {' .
                $on_initialized_javascript .
                '}, 1);' .
                '});';
        }

        $embed_script =
            '<style>img.lazy{min-height:1px}</style>' .
            '<link rel="preload" href="' . esc_url($js_url) . '" as="script">';

        $buffer = preg_replace(
            '~<head(\s+[^>]*)*>~Ui',
            '\\0' . $embed_script, $buffer, 1 
        );

        // load lazyload in footer to make sure DOM is ready at the moment of initialization
        $footer_script =
            '<script>' .
            $on_initialized_javascript_wrapped .
            'window.w3tc_lazyload=1,' .
            'window.lazyLoadOptions=' . $config .
            '</script>' .
            '<script async src="' . esc_url($js_url) . '"></script>';
        $buffer = preg_replace(
            '~</body(\s+[^>]*)*>~Ui',
            $footer_script . '\\0', $buffer, 1 
        );

        return $buffer;
    }

    /**
     * Remove Hummingbird Comment
     */
    public function removeHummingbirdComment()
    {
        global $wphb_cache_config;
        if (isset($wphb_cache_config->cache_identifier) && $wphb_cache_config->cache_identifier ) {
            $wphb_cache_config->cache_identifier = false;
        }
    }

}
