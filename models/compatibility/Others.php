<?php
/**
 * Compatibility Class
 *
 * @file The Others Model file
 * @package HMWP/Compatibility/Others
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Others extends HMWP_Models_Compatibility_Abstract
{

	public function __construct() {
		parent::__construct();

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

		//Compatibility with Smart Slider
		if (HMWP_Classes_Tools::isPluginActive('smart-slider-3/smart-slider-3.php') || HMWP_Classes_Tools::isPluginActive('nextend-smart-slider3-pro/nextend-smart-slider3-pro.php') ) {
			add_filter('hmwp_priority_buffer', '__return_true');
		}

		//Compatibility with Fluent CRM - tested 11162021
		if (HMWP_Classes_Tools::isPluginActive('fluent-crm/fluent-crm.php') || HMWP_Classes_Tools::isPluginActive('fluent-smtp/fluent-smtp.php') ) {
			add_filter('hmwp_option_hmwp_hideajax_paths', '__return_false');
		}

	}

	public function hookAdmin(){

	    //Compatibility with Breakdance plugin
	    if (HMWP_Classes_Tools::isAjax()  ) {
		    if (HMWP_Classes_Tools::getValue('action') == 'query-attachments' ||
		        HMWP_Classes_Tools::getValue('action') == 'breakdance_load_document' ||
		        HMWP_Classes_Tools::getValue('action') == 'breakdance_image_metadata' ||
		        HMWP_Classes_Tools::getValue('action') == 'breakdance_image_sizes') {
			    //Stop Hide My WP Ghost from loading while on editor
			    add_filter('hmwp_process_buffer', '__return_false');
		    }
	    }

    }

	public function hookFrontend() {

        //don't hide login path on CloudPanel and WP Engine
        if(HMWP_Classes_Tools::isCloudPanel() || HMWP_Classes_Tools::isWpengine() ){
            add_filter('hmwp_option_hmwp_hide_login', '__return_false');
        }

		//If in preview mode of the front page
		if (HMWP_Classes_Tools::getValue('hmwp_preview') ) {
			$_COOKIE = array();
			@header_remove("Cookie");
		}

        //Hook the Hide URLs before the plugin
        //Check params and compatibilities
        add_action('plugins_loaded', array( $this, 'fixHideUrls' ), 10);

        //Check if login auth check is required
        add_filter('hmwp_preauth_check', array($this, 'fixRecaptchaCheck'));

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

		//Compatibility with Hyper Cache plugin - tested 01102021
		if (HMWP_Classes_Tools::isPluginActive('hyper-cache/plugin.php') ) {
			add_filter('cache_buffer', array($this, 'findReplaceCache'), PHP_INT_MAX);
		}

		//compatibility with Wp Maintenance plugin - tested 01102021
		if (HMWP_Classes_Tools::isPluginActive('wp-maintenance-mode/wp-maintenance-mode.php') ) {
			add_filter('wpmm_footer', array($this, 'findReplaceBuffer'));
		}

		//Compatibility with Oxygen - tested 01102021
		if (HMWP_Classes_Tools::isPluginActive('oxygen/functions.php') ) {
			add_filter('hmwp_laterload', '__return_true');
		}

		//compatibility with Wp Bakery - tested 01102021
		if (HMWP_Classes_Tools::isPluginActive('js_composer/js_composer.php') ) {
			add_filter('hmwp_option_hmwp_hide_styleids', '__return_false');
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

		//Compatibility with XMl Sitemap - tested 12042023
		//Hide the author in other sitemap plugins
		if (HMWP_Classes_Tools::getOption('hmwp_hide_in_sitemap') && HMWP_Classes_Tools::getOption('hmwp_hide_author_in_sitemap') && isset($_SERVER['REQUEST_URI'])) {

			//XML Sitemap
			if (HMWP_Classes_Tools::isPluginActive('google-sitemap-generator/sitemap.php')) {
				add_action('sm_build_index', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'findReplaceXML'));
				add_action('sm_build_content', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'findReplaceXML'));
			}

			if (HMWP_Classes_Tools::isPluginActive('squirrly-seo/squirrly.php') ) {
				add_filter("sq_sitemap_style", "__return_false", 11);
			}

			//Yoast sitemap
			if (HMWP_Classes_Tools::isPluginActive('wordpress-seo/wp-seo.php')) {
				add_filter("wpseo_stylesheet_url", "__return_false");
			}

			//Rank Math sitemap
			if (HMWP_Classes_Tools::isPluginActive('seo-by-rank-math/rank-math.php') ) {
				if(isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '.xsl') === false) {
					if ($type = str_replace(array('sitemap', '-', '_', '.xml', '/'), '', strtok($_SERVER["REQUEST_URI"], '?'))) {
						if ($type == 'index') $type = 1;
						add_filter("rank_math/sitemap/{$type}_stylesheet_url", "__return_false");
						add_filter('rank_math/sitemap/remove_credit', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'findReplaceXML'));
					}
				}
				add_filter("rank_math/sitemap/remove_credit", "__return_true");
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

		//Change the template directory URL in themes
		if(!HMWP_Classes_Tools::isCachePlugin()) {
			if ((HMWP_Classes_Tools::isThemeActive('Avada') || HMWP_Classes_Tools::isThemeActive('WpRentals')) && !HMWP_Classes_Tools::getOption('hmwp_mapping_file') ) {
				add_filter('template_directory_uri', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'find_replace_url'), PHP_INT_MAX);
			}
		}

        //Gravity Form security fix
        add_filter('wp_redirect', function($redirect, $status = ''){
            //prevent redirect to new login
            if (HMWP_Classes_Tools::getDefault('hmwp_login_url') <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
                if(HMWP_Classes_Tools::getValue('gf_page')){
                    if (strpos($redirect, '/' . HMWP_Classes_Tools::getOption('hmwp_login_url')) !== false) {
                        if (function_exists('is_user_logged_in') && !is_user_logged_in()) {
                            $redirect = home_url();
                        }
                    }
                }
            }

            return $redirect;
        }, PHP_INT_MAX, 2);

	}


    /**
     * Fix compatibility on hide URLs
     *
     * @return void
     */
    public function fixHideUrls() {

        $url = (isset($_SERVER['REQUEST_URI']) ? untrailingslashit(strtok($_SERVER["REQUEST_URI"], '?')) : false);

        //Compatibility with iThemes Security, Temporary Login Plugin, LoginPress, Wordfence
        if ($url && function_exists('is_user_logged_in') && !is_user_logged_in()) {

            //if the /login path is hidden but there is a page with the same URL
            if($url == home_url('login', 'relative') && get_page_by_path('login')){
                add_filter('hmwp_process_hide_urls', '__return_false');
            }

            //If there is a loopback from itsec
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

            //If Ajax
            if(HMWP_Classes_Tools::isAjax()) {
                //?action=backup_guard_awake on backupguard scans
                if (HMWP_Classes_Tools::getValue('action') == 'backup_guard_awake' && HMWP_Classes_Tools::isPluginActive('backup-guard-gold/backup-guard-pro.php')) {
                    add_filter('hmwp_process_hide_urls', '__return_false');
                }

                //?action=hmbkp_cron_test on backupguard scans
                if (HMWP_Classes_Tools::getValue('action') == 'hmbkp_cron_test' && HMWP_Classes_Tools::isPluginActive('backupwordpress/backupwordpress.php')) {
                    add_filter('hmwp_process_hide_urls', '__return_false');
                }
            }

        }
    }

    /**
     * Fix compatibility on brute force recaptcha
     *
     * @param $check
     *
     * @return bool
     */
    public function fixRecaptchaCheck($check) {
        global $hmwp_bruteforce;

        //check if the shortcode was called
        if(isset($hmwp_bruteforce) && $hmwp_bruteforce){
            return true;
        }

        $url = (isset($_SERVER['REQUEST_URI']) ? untrailingslashit(strtok($_SERVER["REQUEST_URI"], '?')) : false);
        $http_post = (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']);

        //check the brute force
        if($check && $url && $http_post  && !HMWP_Classes_Tools::getIsset('brute_ck') && !HMWP_Classes_Tools::getIsset('g-recaptcha-response')){

            if (HMWP_Classes_Tools::getDefault('hmwp_login_url') <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
                $paths = array();
                $paths[] = '/' . HMWP_Classes_Tools::getDefault('hmwp_login_url');
                $paths[] = '/' . HMWP_Classes_Tools::getOption('hmwp_login_url');

                //add lostpass path
                if(HMWP_Classes_Tools::getOption('hmwp_lostpassword_url') <> ''){
                    $paths[] = '/' . HMWP_Classes_Tools::getOption('hmwp_lostpassword_url');
                }

                //add register path
                if(HMWP_Classes_Tools::getOption('hmwp_register_url') <> ''){
                    $paths[] = '/' . HMWP_Classes_Tools::getOption('hmwp_register_url');
                }

                if( $post_id = get_option('woocommerce_myaccount_page_id')){
                    if($post = get_post($post_id)) {
                        $paths[] = '/' . $post->post_name;
                    }
                }

                if (!HMWP_Classes_Tools::searchInString($url, $paths) ) {
                    return false;
                }

                if(HMWP_Classes_Tools::getValue('ltk') && isset($_COOKIE['elementor_pro_login'])){
                    $login_tck = $_COOKIE['elementor_pro_login'];

                    if(HMWP_Classes_Tools::getValue('ltk') == $login_tck){
                        return false;
                    }
                }
            }

        }

        return $check;
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


}
