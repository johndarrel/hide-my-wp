<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMW_Models_Compatibility {

	public function __construct() {
		if ( is_admin() ) {
			add_filter( 'rocket_cache_reject_uri', array( $this, 'rocket_reject_url' ), PHP_INT_MAX );

			//Check compatibility with Really Simple SSL
			if ( HMW_Classes_Tools::isPluginActive( 'really-simple-ssl/rlrsssl-really-simple-ssl.php' ) ) {
				add_action( 'hmw_flushed_rewrites', array( $this, 'checkSimpleSSLRewrites' ) );
			}
		} else {
			defined( 'WPFC_REMOVE_FOOTER_COMMENT' ) || define( 'WPFC_REMOVE_FOOTER_COMMENT', true );
			defined( 'WP_ROCKET_WHITE_LABEL_FOOTPRINT' ) || define( 'WP_ROCKET_WHITE_LABEL_FOOTPRINT', true );

			if ( HMW_Classes_Tools::isPluginActive( 'wp-fastest-cache/wpFastestCache.php' ) ) {
				global $wp_fastest_cache_options;
				$wp_fastest_cache_options = json_decode( get_option( "WpFastestCache" ) );
				if ( isset( $wp_fastest_cache_options->wpFastestCacheStatus ) ) {
					$wp_fastest_cache_options->wpFastestCacheStatus = false;
				}
			}
		}

		//Check boot compatibility for some plugins and functionalities
		$this->checkCompatibilityOnLoad();
	}

	/**
	 * Set the compatibility needed on plugin activation
	 * Called on plugin activation
	 */
	public function install() {
		if ( HMW_Classes_Tools::isPluginActive( 'worker/init.php' ) ) {
			$this->addMUPlugin();
		}
	}


	/**
	 * Delete the compatibility with other plugins
	 * Called on plugin deactivation
	 */
	public function uninstall() {
		$this->deleteMUPlugin();
	}

	public function alreadyCached() {
		if ( did_action( 'wpsupercache_buffer' ) || did_action( 'autoptimize_html_after_minify' ) || did_action( 'rocket_buffer' ) || did_action( 'hmw_buffer' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check some compatibility on page load
	 *
	 */
	public function checkCompatibilityOnLoad() {

		//Compatibility with WPML plugin
		if ( HMW_Classes_Tools::isPluginActive( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			//WPML checks the HTTP_REFERER based on wp-admin and not the custom admin path
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$_SERVER['HTTP_REFERER'] = HMW_Classes_ObjController::getClass( 'HMW_Models_Files' )->getOriginalUrl( $_SERVER['HTTP_REFERER'] );
			}

			// add the correct text direction for WPML plugin
			add_action( 'admin_print_styles', array( $this, 'setTextDirection' ), 1 );

		}

		//Compativility with iThemes security plugin
		if ( HMW_Classes_Tools::isPluginActive( 'ithemes-security-pro/ithemes-security-pro.php' ) ||
		     HMW_Classes_Tools::isPluginActive( 'better-wp-security/better-wp-security.php' ) ) {
			$settings = get_option( 'itsec-storage' );
			if ( isset( $settings['hide-backend']['enabled'] ) && $settings['hide-backend']['enabled'] ) {
				if ( isset( $settings['hide-backend']['slug'] ) && $settings['hide-backend']['slug'] <> '' ) {
					defined( 'HMW_DEFAULT_LOGIN' ) || define( 'HMW_DEFAULT_LOGIN', $settings['hide-backend']['slug'] );
					HMW_Classes_Tools::$options['hmw_login_url'] = HMW_Classes_Tools::$default['hmw_login_url'];
				}
			}
		}


		//Add Compatibility with PPress plugin
		if ( HMW_Classes_Tools::isPluginActive( 'ppress/profilepress.php' ) ) {
			add_action( 'hmw_login_init', array( $this, 'ppressCompatibility' ) );
		}

		if ( ! is_admin() ) {
			try {
				//Robots.txt compatibility with other plugins
				if ( HMW_Classes_Tools::getOption( 'hmw_robots' ) ) {
					if ( HMW_Classes_ObjController::getClass( 'HMW_Models_Files' )->isFile( $_SERVER['REQUEST_URI'] ) ) {
						//Compatibility with Squirrly SEO x-cf-powered-by
						if ( HMW_Classes_Tools::isPluginActive( 'squirrly-seo/squirrly.php' ) ) {
							add_filter( 'sq_robots', array(
								HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
								'replace_robots'
							), 11 );
						} else {
							if ( strpos( $_SERVER['REQUEST_URI'], 'robots.txt' ) ) {
								HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->replace_robots( false );
							}
						}
					}
				}
			} catch ( Exception $e ) {
			}
		}

	}

	/**
	 * Check other plugins and set compatibility settings
	 */
	public function checkCompatibility() {
		//don't let to rename and hide the current paths if logout is required
		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
			return;
		}

		if ( ! is_admin() ) {

			//compatibility with Wp Maintenance plugin
			if ( HMW_Classes_Tools::isPluginActive( 'wp-maintenance-mode/wp-maintenance-mode.php' ) ) {
				add_filter( 'wpmm_footer', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'getTempBuffer'
				) );
			}

			//Chech if the users set to change for logged users users
			//don't let cache plugins to change the paths is not needed
			if ( ! HMW_Classes_Tools::doChangesAdmin() ) {
				add_filter( 'hmw_laterload', array( 'HMW_Classes_Tools', 'returnFalse' ) );
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnFalse' ) );

				return;
			}

			//Change the template directory URL in themes
			if ( HMW_Classes_Tools::isThemeActive( 'Avada' ) || HMW_Classes_Tools::isThemeActive( 'WpRentals' ) ) {
				add_filter( 'template_directory_uri', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace_url'
				), PHP_INT_MAX );
			}

			//Compatibility with Squirrly SEO
			if ( HMW_Classes_Tools::isPluginActive( 'squirrly-seo/squirrly.php' ) ) {
				add_filter( 'sq_buffer', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace'
				), PHP_INT_MAX );
			}

			//Compatibility with WP-rocket plugin
			if ( HMW_Classes_Tools::isPluginActive( 'wp-rocket/wp-rocket.php' ) ) {
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnFalse' ) );

				add_filter( 'rocket_buffer', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace'
				), PHP_INT_MAX );

				add_filter( 'rocket_cache_busting_filename', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace_url'
				), PHP_INT_MAX );
				add_filter( 'rocket_iframe_lazyload_placeholder', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace_url'
				), PHP_INT_MAX );

				return;
			}


			//Compatibility with CDN Enabler
			if ( HMW_Classes_Tools::isPluginActive( 'hummingbird-performance/wp-hummingbird.php' ) ) {
				add_filter( 'hmw_laterload', array( 'HMW_Classes_Tools', 'returnTrue' ) );
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnTrue' ) );

				return;
			}

			//Compatibility with Wp Super Cache Plugin
			if ( HMW_Classes_Tools::isPluginActive( 'wp-super-cache/wp-cache.php' ) ) {
				//add_filter('hmw_laterload', array('HMW_Classes_Tools', 'returnFalse'));
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnTrue' ) );

				add_filter( 'wpsupercache_buffer', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace'
				), PHP_INT_MAX );

				return;
			}

			//Compatibility with CDN Enabler
			if ( HMW_Classes_Tools::isPluginActive( 'cdn-enabler/cdn-enabler.php' ) ) {
				add_filter( 'hmw_laterload', array( 'HMW_Classes_Tools', 'returnTrue' ) );
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnTrue' ) );

				return;
			}


			//Compatibility with Autoptimize plugin
			if ( HMW_Classes_Tools::isPluginActive( 'autoptimize/autoptimize.php' ) ) {
				add_filter( 'hmw_laterload', array( 'HMW_Classes_Tools', 'returnFalse' ) );
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnTrue' ) );

				if ( HMW_Classes_Tools::isPluginActive( 'wp-smush-pro/wp-smush.php' ) ) {
					if ( $smush = get_option( 'wp-smush-cdn_status' ) ) {
						if ( isset( $smush->cdn_enabled ) && $smush->cdn_enabled ) {
							return;
						}
					}
				}

				add_filter( 'autoptimize_html_after_minify', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace'
				), PHP_INT_MAX );

				return;
			}

			if ( HMW_Classes_Tools::isPluginActive( 'wp-asset-clean-up/wpacu.php' ) || HMW_Classes_Tools::isPluginActive( 'wp-asset-clean-up-pro/wpacu.php' ) ) {
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnFalse' ) );
				add_filter( 'hmw_laterload', array( 'HMW_Classes_Tools', 'returnFalse' ) );

				add_filter( 'wpacu_html_source', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace'
				), PHP_INT_MAX );

				return;
			}

			//Patch for WOT Cache plugin
			if ( defined( 'WOT_VERSION' ) ) {
				add_filter( 'hmw_laterload', array( 'HMW_Classes_Tools', 'returnTrue' ) );
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnTrue' ) );

				add_filter( 'wot_cache', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace'
				), PHP_INT_MAX );

				return;
			}

			//For woo-global-cart plugin
			if ( defined( 'WOOGC_VERSION' ) ) {
				remove_all_actions( 'shutdown', 1 );
				add_filter( 'hmw_buffer', array( $this, 'fix_woogc_shutdown' ) );

				return;
			}

			if ( HMW_Classes_Tools::isPluginActive( 'cache-enabler/cache-enabler.php' ) ) {
				add_filter( 'hmw_laterload', array( 'HMW_Classes_Tools', 'returnFalse' ) );
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnTrue' ) );

				return;
			}

			//Compatibility with Wp Fastest Cache
			if ( HMW_Classes_Tools::isPluginActive( 'wp-fastest-cache/wpFastestCache.php' ) ) {

				add_filter( 'wpfc_buffer_callback_filter', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace'
				), PHP_INT_MAX );

				add_filter( 'hmw_laterload', array( 'HMW_Classes_Tools', 'returnTrue' ) );
				add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnTrue' ) );

				return;
			}

			//Compatibility with Powered Cache
			if ( HMW_Classes_Tools::isPluginActive( 'powered-cache/powered-cache.php' ) ) {
				global $powered_cache_options;

				if ( apply_filters( 'powered_cache_lazy_load_enabled', true ) ) {
					add_filter( 'hmw_process_buffer', array( 'HMW_Classes_Tools', 'returnTrue' ) );
				}

				add_filter( 'powered_cache_page_caching_buffer', array(
					HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' ),
					'find_replace'
				), PHP_INT_MAX );
				if ( isset( $powered_cache_options ) ) {
					$powered_cache_options['show_cache_message'] = false;
				}

				return;
			}

			//Compatibility with W3 Total cache
			if ( HMW_Classes_Tools::isPluginActive( 'w3-total-cache/w3-total-cache.php' ) ) {
				//Don't show comments
				add_filter( 'w3tc_can_print_comment', array( 'HMW_Classes_Tools', 'returnFalse' ), PHP_INT_MAX );

				return;
			}

		}
	}

	public static function getAlerts() {
		//is CDN plugin installed
		if ( is_admin() || is_network_admin() ) {
			if ( HMW_Classes_Tools::isPluginActive( 'cdn-enabler/cdn-enabler.php' ) ) {
				if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) <> 'default' ) {
					if ( $cdn_enabler = get_option( 'cdn_enabler' ) ) {
						if ( isset( $cdn_enabler['dirs'] ) ) {
							$dirs = explode( ',', $cdn_enabler['dirs'] );
							if ( ! empty( $dirs ) &&
							     ! in_array( HMW_Classes_Tools::getOption( 'hmw_wp-content_url' ), $dirs ) &&
							     ! in_array( HMW_Classes_Tools::getOption( 'hmw_wp-includes_url' ), $dirs )
							) {
								HMW_Classes_Error::setError( __( 'CDN Enabled detected. Please include the new wp-content and wp-includes paths in CDN Enabler Settings', _HMW_PLUGIN_NAME_ ), 'default' );
							}
						}
					}
				}

				if ( isset( $_SERVER["REQUEST_URI"] ) ) {
					if ( admin_url( 'options-general.php?page=cdn_enabler', 'relative' ) == $_SERVER['REQUEST_URI'] ) {
						HMW_Classes_Error::setError( sprintf( __( "CDN Enabler detected! Learn how to configure it with Hide My WP %sClick here%s", _HMW_PLUGIN_NAME_ ), '<a href="https://hidemywpghost.com/hide-my-wp-and-cdn-enabler/" target="_blank">', '</a>' ), 'error' );
					}
				}
			}

			if ( HMW_Classes_Tools::isPluginActive( 'wp-super-cache/wp-cache.php' ) ) {
				if ( get_option( 'ossdl_off_cdn_url' ) <> '' && get_option( 'ossdl_off_cdn_url' ) <> home_url() ) {
					$dirs = explode( ',', get_option( 'ossdl_off_include_dirs' ) );
					if ( ! empty( $dirs ) &&
					     ! in_array( HMW_Classes_Tools::getOption( 'hmw_wp-content_url' ), $dirs ) &&
					     ! in_array( HMW_Classes_Tools::getOption( 'hmw_wp-includes_url' ), $dirs )
					) {
						HMW_Classes_Error::setError( sprintf( __( 'WP Super Cache CDN detected. Please include %s and %s paths in WP Super Cache > CDN > Include directories', _HMW_PLUGIN_NAME_ ), '<strong>' . HMW_Classes_Tools::getOption( 'hmw_wp-content_url' ) . '</strong>', '<strong>' . HMW_Classes_Tools::getOption( 'hmw_wp-includes_url' ) . '</strong>' ), 'default' );
					}
				}
			}

			//Mor Rewrite is not installed
			if ( HMW_Classes_Tools::isApache() && ! HMW_Classes_Tools::isModeRewrite() ) {
				HMW_Classes_Error::setError( sprintf( __( 'Hide My WP does not work without mode_rewrite. Please activate the rewrite module in Apache. %sMore details%s', _HMW_PLUGIN_NAME_ ), '<a href="https://tecadmin.net/enable-apache-mod-rewrite-module-in-ubuntu-linuxmint/" target="_blank">', '</a>' ) );
			}

			//No permalink structure
			if ( ! HMW_Classes_Tools::isPermalinkStructure() ) {
				HMW_Classes_Error::setError( sprintf( __( 'Hide My WP does not work with %s Permalinks. Change it to %s or other type in Settings > Permalinks in order to hide it', _HMW_PLUGIN_NAME_ ), __( 'Plain' ), __( 'Post Name' ) ) );
				defined( 'HMW_DISABLE' ) || define( 'HMW_DISABLE', true );
			} else {
				//IIS server and no Rewrite Permalinks installed
				if ( HMW_Classes_Tools::isIIS() && HMW_Classes_Tools::isPHPPermalink() ) {
					HMW_Classes_Error::setError( sprintf( __( 'You need to activate the URL Rewrite for IIS to be able to change the permalink structure to friendly URL (without index.php). %sMore details%s', _HMW_PLUGIN_NAME_ ), '<a href="https://www.iis.net/downloads/microsoft/url-rewrite" target="_blank">', '</a>' ) );
				} elseif ( HMW_Classes_Tools::isPHPPermalink() ) {
					HMW_Classes_Error::setError( __( 'You need to set the permalink structure to friendly URL (without index.php).', _HMW_PLUGIN_NAME_ ) );
				}
			}

			if ( HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->isConfigAdminCookie() ) {
				HMW_Classes_Error::setError( __( 'The constant ADMIN_COOKIE_PATH is defined in wp-config.php by another plugin. Hide My WP will not work unless you remove the line define(\'ADMIN_COOKIE_PATH\', ...);', _HMW_PLUGIN_NAME_ ) );
				defined( 'HMW_DISABLE' ) || define( 'HMW_DISABLE', true );
			}

			//Inmotion server detected
			if ( HMW_Classes_Tools::isInmotion() ) {
				HMW_Classes_Error::setError( sprintf( __( 'Inmotion detected. %sPlease read how to make the plugin compatible with Inmotion Nginx Cache%s', _HMW_PLUGIN_NAME_ ), '<a href="https://hidemywpghost.com/hide-my-wp-pro-compatible-with-inmotion-wordpress-hosting/" target="_blank">', '</a>' ) );
			}

			//The login path is changed by other plugins and may affect the functionality
			if ( HMW_Classes_Tools::$default['hmw_login_url'] == HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) {
				if ( strpos( site_url( 'wp-login.php' ), HMW_Classes_Tools::$default['hmw_login_url'] ) === false ) {
					defined( 'HMW_DEFAULT_LOGIN' ) || define( 'HMW_DEFAULT_LOGIN', site_url( 'wp-login.php' ) );
				}
			}

			//The admin URL is already changed by other plugins and may affect the functionality
			if ( ! HMW_RULES_IN_CONFIG ) {
				HMW_Classes_Error::setError( __( 'Hide My WP rules are not saved in the config file and this may affect the website loading speed.', _HMW_PLUGIN_NAME_ ) );
				defined( 'HMW_DEFAULT_ADMIN' ) || define( 'HMW_DEFAULT_ADMIN', HMW_Classes_Tools::$default['hmw_admin_url'] );
			} elseif ( HMW_Classes_Tools::$default['hmw_admin_url'] == HMW_Classes_Tools::getOption( 'hmw_admin_url' ) ) {
				if ( strpos( admin_url(), HMW_Classes_Tools::$default['hmw_admin_url'] ) === false ) {
					defined( 'HMW_DEFAULT_ADMIN' ) || define( 'HMW_DEFAULT_ADMIN', admin_url() );
				}
			}


			if ( HMW_Classes_Tools::isGodaddy() ) {
				HMW_Classes_Error::setError( sprintf( __( "Godaddy detected! To avoid CSS errors, make sure you switch off the CDN from %s", _HMW_PLUGIN_NAME_ ), '<strong>' . '<a href="https://hidemywpghost.com/how-to-use-hide-my-wp-with-godaddy/" target="_blank"> Godaddy > Managed WordPress > Overview</a>' . '</strong>' ) );
			}

			//Check if the rules are working as expected
			if ( HMW_Classes_Tools::getOption( 'rewrites' ) ) {
				HMW_Classes_Error::setError( sprintf( __( 'Attention! Please check the rewrite rules in the config file. Some URLs passed through the config file rules and are loaded through WordPress which may slow down your website. Please follow this tutorial: %s', _HMW_PLUGIN_NAME_ ), '<a href="https://hidemywpghost.com/kb/when-the-website-loads-slower-with-hide-my-wp-ghost/" target="_blank" class="text-warning">https://hidemywpghost.com/kb/when-the-website-loads-slower-with-hide-my-wp-ghost/</a>' ), 'text-white bg-danger' );

			}

		}


	}

	public function rocket_reject_url( $uri ) {
		if ( HMW_Classes_Tools::$default['hmw_login_url'] <> HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) {
			$path  = parse_url( home_url(), PHP_URL_PATH );
			$uri[] = ( $path <> '/' ? $path . '/' : $path ) . HMW_Classes_Tools::getOption( 'hmw_login_url' );
		}

		return $uri;
	}


	/**
	 * Include CDNs if found
	 * @return array|false
	 */
	public function findCDNServers() {
		$domains = array();

		HMW_Debug::dump( "findCDNServers", HMW_Classes_Tools::isPluginActive( 'wp-rocket/wp-rocket.php' ) );

		if ( HMW_Classes_Tools::isPluginActive( 'wp-rocket/wp-rocket.php' ) && function_exists( 'get_rocket_option' ) ) {
			HMW_Debug::dump( "wp rocket installed" );
			$cnames = get_rocket_option( 'cdn_cnames', array() );
			foreach ( $cnames as $k => $_urls ) {
				HMW_Debug::dump( $_urls );
				$_urls = explode( ',', $_urls );
				$_urls = array_map( 'trim', $_urls );

				foreach ( $_urls as $url ) {
					$domains[] = $url;
				}
			}
		}

		if ( HMW_Classes_Tools::isPluginActive( 'cdn-enabler/cdn-enabler.php' ) ) {
			if ( $cd_enabler = get_option( 'cdn_enabler' ) ) {
				if ( isset( $cd_enabler['url'] ) ) {
					$domains[] = $cd_enabler['url'];
				}
			}
		}

		if ( HMW_Classes_Tools::isPluginActive( 'powered-cache/powered-cache.php' ) ) {
			global $powered_cache_options;
			if ( isset( $powered_cache_options['cdn_hostname'] ) ) {
				$hostnames = $powered_cache_options['cdn_hostname'];
				if ( ! empty( $hostnames ) ) {
					foreach ( $hostnames as $host ) {
						if ( ! empty( $host ) ) {
							$domains[] = $host;
						}
					}
				}
			}
		}

		if ( HMW_Classes_Tools::isPluginActive( 'wp-super-cache/wp-cache.php' ) ) {
			if ( get_option( 'ossdl_off_cdn_url' ) <> '' && get_option( 'ossdl_off_cdn_url' ) <> home_url() ) {
				$domains[] = get_option( 'ossdl_off_cdn_url' );
			}
		}

		if ( HMW_Classes_Tools::isPluginActive( 'wp-smush-pro/wp-smush.php' ) ) {
			if ( $smush = get_option( 'wp-smush-cdn_status' ) ) {
				if ( isset( $smush->cdn_enabled ) && $smush->cdn_enabled ) {
					if ( isset( $smush->endpoint_url ) && isset( $smush->site_id ) ) {
						$domains[] = 'https://' . $smush->endpoint_url . '/' . $smush->site_id;
					}
				}
			}
		}

		if ( ! empty( $domains ) ) {
			return $domains;
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
	public function fix_woogc_shutdown( $buffer ) {
		global $blog_id, $woocommerce, $WooGC;;

		if ( ! class_exists( 'WooGC' ) ) {
			return $buffer;
		}

		if ( ! is_object( $woocommerce->cart ) ) {
			return $buffer;
		}


		if ( ! $WooGC instanceof WooGC ) {
			return $buffer;
		}

		$options      = $WooGC->functions->get_options();
		$blog_details = get_blog_details( $blog_id );

		//replace any checkout links
		if ( ! empty( $options['cart_checkout_location'] ) && $options['cart_checkout_location'] != $blog_id ) {
			$checkout_url = $woocommerce->cart->get_checkout_url();
			$checkout_url = str_replace( array( 'http:', 'https:' ), "", $checkout_url );
			$checkout_url = trailingslashit( $checkout_url );

			$buffer = str_replace( $blog_details->domain . "/checkout/", $checkout_url, $buffer );

		}

		return $buffer;
	}


	/**
	 * Add rules to be compatible with Simple SSL plugins
	 */
	public function checkSimpleSSLRewrites() {
		global $wp_filesystem;
		try {
			$options = get_option( 'rlrsssl_options' );

			if ( isset( $options['htaccess_redirect'] ) && $options['htaccess_redirect'] ) {
				if ( method_exists( $wp_filesystem, 'get_contents' ) && method_exists( $wp_filesystem, 'put_contents' ) ) {

					$config_file = HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->getConfFile();
					$htaccess    = $wp_filesystem->get_contents( $config_file );
					preg_match( "/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s", $htaccess, $match );

					if ( isset( $match[0] ) && ! empty( $match[0] ) ) {
						$htaccess = preg_replace( "/#\s?BEGIN\s?rlrssslReallySimpleSSL.*?#\s?END\s?rlrssslReallySimpleSSL/s", "", $htaccess );
						$htaccess = $match[0] . PHP_EOL . $htaccess;
						$htaccess = preg_replace( "/\n+/", "\n", $htaccess );
						$wp_filesystem->put_contents( $config_file, $htaccess );
					}

				}
			}
		} catch ( Exception $e ) {
		}
	}

	/************************************************************ Must Use Plugin (needed for Manage WP and other cache plugins) */

	/**
	 * Add the Must Use plugin to make sure is loading for the custom wp-admin path every time
	 *
	 */
	public function addMUPlugin() {
		try {
			$this->registerMUPlugin( '0-hidemywp.php', $this->buildLoaderContent( 'hide-my-wp/index.php' ) );
		} catch ( Exception $e ) {
		}
	}

	/**
	 * Remove the Must Use plugin on deactivation
	 */
	public function deleteMUPlugin() {
		try {
			$this->deregisterMUPlugin( '0-hidemywp.php' );
		} catch ( Exception $e ) {
		}
	}

	/**
	 * The MU plugin content
	 *
	 * @param $pluginBasename
	 *
	 * @return string
	 */
	public function buildLoaderContent( $pluginBasename ) {
		return "<?php
        /*
        Plugin Name: HideMyWP - Ghost Loader
        Description: This is automatically generated by the hideMyWP plugin to increase performance and reliability. It is automatically disabled when disabling the main plugin.
        */
        
        if (function_exists('untrailingslashit') && defined('WP_PLUGIN_DIR') && file_exists(untrailingslashit(WP_PLUGIN_DIR).'/$pluginBasename')) {
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
	public function registerMUPlugin( $loaderName, $loaderContent ) {
		global $wp_filesystem;

		if ( method_exists( $wp_filesystem, 'get_contents' ) && method_exists( $wp_filesystem, 'put_contents' ) ) {

			$mustUsePluginDir = rtrim( WPMU_PLUGIN_DIR, '/' );
			$loaderPath       = $mustUsePluginDir . '/' . $loaderName;

			if ( file_exists( $loaderPath ) && md5( $loaderContent ) === md5_file( $loaderPath ) ) {
				return;
			}

			if ( ! is_dir( $mustUsePluginDir ) ) {
				@mkdir( $mustUsePluginDir );
			}

			if ( is_writable( $mustUsePluginDir ) ) {
				$wp_filesystem->put_contents( $loaderPath, $loaderContent );
			}

		}
	}

	/**
	 * Delete the MU file
	 *
	 * @param $loaderName
	 */
	public function deregisterMUPlugin( $loaderName ) {
		$mustUsePluginDir = rtrim( WPMU_PLUGIN_DIR, '/' );
		$loaderPath       = $mustUsePluginDir . '/' . $loaderName;

		if ( ! file_exists( $loaderPath ) ) {
			return;
		}

		@unlink( $loaderPath );
	}

	/**
	 * Force the DB text direction for some themes and plugins
	 */
	public function setTextDirection() {

		if ( function_exists( 'get_current_user_id' ) ) {
			$user_id   = get_current_user_id();
			$direction = get_user_meta( $user_id, 'rtladminbar', true );

			global $wp_styles;
			if ( isset( $wp_styles->text_direction ) ) {
				$wp_styles->text_direction = $direction;
			}
		}

	}

	/**
	 * Add Compatibility with PPress plugin
	 * Load the post from Ppress for the login page
	 */
	public function ppressCompatibility() {
		//Add compatibility with PPress plugin
		$data = get_option( 'pp_settings_data' );
		if ( class_exists( 'WP_Query' ) && isset( $data['set_login_url'] ) && (int) $data['set_login_url'] > 0 ) {
			$query = new WP_Query( array( 'p' => $data['set_login_url'], 'post_type' => 'any' ) );
			if ( $query->have_posts() ) {
				$query->the_post();
				get_header();
				the_content();
				get_footer();
			}
			exit();
		}
	}
}