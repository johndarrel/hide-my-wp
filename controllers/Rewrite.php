<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Controllers_Rewrite extends HMW_Classes_FrontController {

    public function __construct() {
        parent::__construct();

        if (defined('HMW_DISABLE') && HMW_DISABLE) {
            return;
        }

        //Start the buffer only if priority is set
        if (HMW_PRIORITY) {
            $this->model->startBuffer();
        }

        //Init the main hooks
        $this->initHooks();
    }

    /**
     * Init the hooks for hide my wp
     */
    public function initHooks() {
        if (HMW_Classes_Tools::isPermalinkStructure()) {
            if (HMW_Classes_Tools::isApache() && !HMW_Classes_Tools::isModeRewrite()) {
                return;
            }

            if (!HMW_Classes_Tools::getOption('error') && !HMW_Classes_Tools::getOption('logout')) {
                //rename the author if set so
                add_filter('author_rewrite_rules', array($this->model, 'author_url'), 99, 1);
            }

            add_filter('query_vars', array($this->model, 'addParams'), 1, 1);
            add_action('login_init', array($this->model, 'login_init'), 1);
            add_filter('login_redirect', array($this->model, 'sanitize_redirect'), 9, 3);
	        add_action('login_head', array($this->model, 'login_head'), PHP_INT_MAX);
	        add_action('wp_logout', array($this->model, 'wp_logout'), PHP_INT_MAX);

            //change the admin url
            add_filter('lostpassword_url', array($this->model, 'lostpassword_url'), PHP_INT_MAX, 1);
            add_filter('register', array($this->model, 'register_url'), PHP_INT_MAX, 1);
            add_filter('login_url', array($this->model, 'login_url'), PHP_INT_MAX, 1);
            add_filter('logout_url', array($this->model, 'logout_url'), PHP_INT_MAX, 2);
            add_filter('admin_url', array($this->model, 'admin_url'), PHP_INT_MAX, 3);
            add_filter('network_admin_url', array($this->model, 'network_admin_url'), PHP_INT_MAX, 3);
            add_filter('site_url', array($this->model, 'site_url'), PHP_INT_MAX, 2);
            add_filter('network_site_url', array($this->model, 'site_url'), PHP_INT_MAX, 3);

            //check and set the cookied for the modified urls
            HMW_Classes_ObjController::getClass('HMW_Models_Cookies');
            //load the compatibility class
            HMW_Classes_ObjController::getClass('HMW_Models_Compatibility');
        }

        //Load the PluginLoaded Hook
        add_action('plugins_loaded', array($this, 'hookPreload'), 1);
        //just to make sure it called in case plugins_loaded is not triggered
        add_action('template_redirect', array($this, 'hookPreload'), 1);

        //in case of broken URL, try to load it
        if(!is_admin() && (HMW_Classes_Tools::getOption('configure_error') || HMW_SAFEMODE)) {
            add_action('template_redirect', array(HMW_Classes_ObjController::getClass('HMW_Models_Files'), 'checkBrokenFile'), PHP_INT_MAX);
            add_action('init', array(HMW_Classes_ObjController::getClass('HMW_Models_Files'), 'checkAdminPath'), PHP_INT_MAX);
        }
    }


    public function hookPreload() {
        //if plugin_loaded then remove template_redirect
        if (!did_action('template_redirect')) {
            remove_action('template_redirect', array($this, 'hookPreload'), 1);
        }

        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        //Make sure is permalink set up
        if (HMW_Classes_Tools::isPermalinkStructure()) {
            if (HMW_Classes_Tools::isApache() && !HMW_Classes_Tools::isModeRewrite()) {
                return;
            }

            //Don't go further if the safe parameter is set
            if (HMW_Classes_Tools::getIsset(HMW_Classes_Tools::getOption('hmw_disable_name'))) {
                if (HMW_Classes_Tools::getValue(HMW_Classes_Tools::getOption('hmw_disable_name')) == HMW_Classes_Tools::getOption('hmw_disable')) {
                    return;
                }
            }

            //Build the find_replace list
            $this->model->buildRedirect();

            //don't let to rename and hide the current paths if logout is required
            if (HMW_Classes_Tools::getOption('error') || HMW_Classes_Tools::getOption('logout')) {
                return;
            }

            //stop here is the option is default.
            //the prvious code is needed for settings change and validation
            if (HMW_Classes_Tools::getOption('hmw_mode') == 'default') {
                return;
            }

            //Hide the paths in ajax
            if (HMW_Classes_Tools::isAjax()) {
                $this->model->startBuffer();

                //hide the URLs from admin and login
                add_action('init', array($this->model, 'hideUrls'), 99);

                return;
            }

            //Check Compatibilities with ther plugins
            HMW_Classes_ObjController::getClass('HMW_Models_Compatibility')->checkCompatibility();

            //Start the Buffer if not late loading
            $hmw_laterload = apply_filters('hmw_laterload', HMW_Classes_Tools::getOption('hmw_laterload'));

            //check lateload
            if ($hmw_laterload && !did_action('template_redirect')) {
                add_action('template_redirect', array($this->model, 'startBuffer'), PHP_INT_MAX);
            } else {
                //start the buffer now
                $this->model->startBuffer();
            }

            //Check the buffer on shutdown
            if (HMW_Classes_Tools::getOption('hmw_shutdown_load')) {
                add_action('shutdown', array($this->model, 'shutDownBuffer'), 0);
            }

            //hide the URLs from admin and login
            add_action('init', array($this->model, 'hideUrls'), 99);

	        //hide headers added by plugins
	        add_action('template_redirect', array($this->model, 'hideHeaders'), PHP_INT_MAX);

	        if (!is_admin()) {
		        if ( HMW_Classes_Tools::getOption( 'hmw_hide_version' ) ) {
			        add_filter( 'the_generator', array( 'HMW_Classes_Tools', 'returnFalse' ), 99, 1 );
			        remove_action( 'wp_head', 'wp_generator' );
			        remove_action( 'wp_head', 'wp_resource_hints', 2 );
		        }

		        if (HMW_Classes_Tools::getOption('hmw_disable_emojicons')) {
			        //disable the emoji icons
			        $this->disable_emojicons();
		        }

		        if (HMW_Classes_Tools::getOption('hmw_disable_rest_api')) {
			        //disable the rest_api
			        if (!HMW_Classes_Tools::isPluginActive('contact-form-7/wp-contact-form-7.php')) {
				        if (!function_exists('is_user_logged_in') || (function_exists('is_user_logged_in') && !is_user_logged_in())) {
					        $this->disable_rest_api();
				        }
			        }
		        }

		        if (HMW_Classes_Tools::getOption('hmw_disable_xmlrpc')) {
			        add_filter('xmlrpc_enabled', array('HMWP_Classes_Tools', 'returnFalse'));
		        }

		        if (HMW_Classes_Tools::getOption('hmw_disable_embeds')) {
			        //disable the embeds
			        $this->disable_embeds();
		        }

		        //Windows Live Write
		        if (HMW_Classes_Tools::getOption('hmw_disable_manifest')) {
			        //disable the embeds
			        $this->disable_manifest();
		        }

		        //Really Simple Discovery
		        if (HMW_Classes_Tools::getOption('hmw_hide_header')) {
			        $this->disable_rds();
		        }

		        if (HMW_Classes_Tools::getOption('hmw_hide_comments')) {
			        $this->disable_comments();
		        }

		        //Disable Database Debug
		        if (HMW_Classes_Tools::getOption('hmw_disable_debug')) {
			        global $wpdb;
			        $wpdb->hide_errors();
		        }
	        }

        }


    }


    /**
     *  On admin init
     *  Load the Menu
     *  If the user changes the Permalink to default ... prevent errors
     */
    public function hookInit() {
        if (HMW_Classes_Tools::getIsset(HMW_Classes_Tools::getOption('hmw_disable_name'))) {
            if (HMW_Classes_Tools::getValue(HMW_Classes_Tools::getOption('hmw_disable_name')) == HMW_Classes_Tools::getOption('hmw_disable')) {
                return;
            }
        }

        //If the user changes the Permalink to default ... prevent errors
        if (!HMW_Classes_Tools::isPermalinkStructure()) {
            if (current_user_can('manage_options')) {
                if (HMW_Classes_Tools::$default['hmw_admin_url'] <> HMW_Classes_Tools::getOption('hmw_admin_url')) {
                    $this->model->flushChanges();
                }
            }
        }

        //Show the menu for admins only
        if (current_user_can('manage_options')) {
            HMW_Classes_ObjController::getClass('HMW_Controllers_Menu')->hookInit();
        }


    }


    /**
     * Disable the emoji icons
     */
    public function disable_emojicons() {

        // all actions related to emojis
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        add_filter('emoji_svg_url', array('HMW_Classes_Tools','returnFalse'));

        // filter to remove TinyMCE emojis
        add_filter('tiny_mce_plugins', array($this, 'disable_emojicons_tinymce'));
    }

    function disable_emojicons_tinymce($plugins) {
        if (is_array($plugins)) {
            return array_diff($plugins, array('wpemoji'));
        } else {
            return array();
        }
    }

    /**
     * Disable the Rest Api access
     */
    public function disable_rest_api() {
        remove_action('init', 'rest_api_init');
        remove_action('rest_api_init', 'rest_api_default_filters', 10);
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('parse_request', 'rest_api_loaded');
    }

    /**
     * Disable the embeds
     */
    public function disable_embeds() {
        // Remove the REST API endpoint.
        remove_action('rest_api_init', 'wp_oembed_register_route');

        // Turn off oEmbed auto discovery.
        // Don't filter oEmbed results.
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

        // Remove oEmbed discovery links.
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        // Remove oEmbed-specific JavaScript from the front-end and back-end.
        remove_action('wp_head', 'wp_oembed_add_host_js');
    }

    /**
     * Disable Windows Live Write
     */
    public function disable_manifest() {
        remove_action('wp_head', 'wlwmanifest_link');
    }

    /**
     * Disable Really Simple Discovery
     */
    public function disable_rds() {
        remove_action('wp_head', 'rsd_link');
    }

	public function disable_comments(){
		global $wp_super_cache_comments;
		remove_all_filters( 'w3tc_footer_comment' );
		$wp_super_cache_comments = false;
	}

}
