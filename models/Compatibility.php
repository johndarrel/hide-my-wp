<?php
/**
 * Compatibility Model
 * Handles the compatibility with the rest of the plugins and themes
 *
 * @file  The Compatibility file
 * @package HMWP/Compatibility
 * @since 6.0.0
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility {

	protected $paths = array();

	/**
	 * Set the compatibility needed on plugin activation
	 * Called on plugin activation
	 */
	public function install() {
		if ( HMWP_Classes_Tools::isPluginActive( 'worker/init.php' ) ) {
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

	/**
	 * Check some compatibility on page load
	 */
	public function checkCompatibility() {

		$compatibilities = array(
			'really-simple-ssl/rlrsssl-really-simple-ssl.php'                               => 'HMWP_Models_Compatibility_ReallySimpleSsl',
			'nitropack/main.php'                                                            => 'HMWP_Models_Compatibility_Nitropack',
			'hummingbird-performance/wp-hummingbird.php'                                    => 'HMWP_Models_Compatibility_Hummingbird',
			'wp-rocket/wp-rocket.php'                                                       => 'HMWP_Models_Compatibility_WpRocket',
			'wp-fastest-cache/wpFastestCache.php'                                           => 'HMWP_Models_Compatibility_FastestCache',
			'woocommerce/woocommerce.php'                                                   => 'HMWP_Models_Compatibility_Woocommerce',
			'memberpress/memberpress.php'                                                   => 'HMWP_Models_Compatibility_MemberPress',
			'autoptimize/autoptimize.php'                                                   => 'HMWP_Models_Compatibility_Autoptimize',
			'confirm-email/confirm-email.php'                                               => 'HMWP_Models_Compatibility_ConfirmEmail',
			'breeze/breeze.php'                                                             => 'HMWP_Models_Compatibility_Breeze',
			'w3-total-cache/w3-total-cache.php'                                             => 'HMWP_Models_Compatibility_W3Total',
			'jch-optimize/jch-optimize.php'                                                 => 'HMWP_Models_Compatibility_JsOptimize',
			'minimal-coming-soon-maintenance-mode/minimal-coming-soon-maintenance-mode.php' => 'HMWP_Models_Compatibility_MMaintenance',
			'all-in-one-wp-security-and-firewall/wp-security.php'                           => 'HMWP_Models_Compatibility_AioSecurity',
			'powered-cache/powered-cache.php'                                               => 'HMWP_Models_Compatibility_PowerCache',
			'squirrly-seo/squirrly.php'                                                     => 'HMWP_Models_Compatibility_Squirrly',
			'siteguard/siteguard.php'                                                       => 'HMWP_Models_Compatibility_SiteGuard',
			'sg-cachepress/sg-cachepress.php'                                               => 'HMWP_Models_Compatibility_SiteGuard',
			'wordfence/wordfence.php'                                                       => 'HMWP_Models_Compatibility_Wordfence',
			'sitepress-multilingual-cms/sitepress.php'                                      => 'HMWP_Models_Compatibility_Wpml',
			'ithemes-security-pro/ithemes-security-pro.php'                                 => 'HMWP_Models_Compatibility_iThemes',
			'better-wp-security/better-wp-security.php'                                     => 'HMWP_Models_Compatibility_iThemes',
			'ultimate-member/ultimate-member.php'                                           => 'HMWP_Models_Compatibility_UltimateMember',
			'wp-user-manager/wp-user-manager.php'                                           => 'HMWP_Models_Compatibility_Wpum',
			'wp-defender/wp-defender.php'                                                   => 'HMWP_Models_Compatibility_WpDefender',
			'cmp-coming-soon-maintenance/niteo-cmp.php'                                     => 'HMWP_Models_Compatibility_Cmp',
			'display-admin-page-on-frontend-premium/index.php'                              => 'HMWP_Models_Compatibility_WPFrontendAdmin',
			'flying-press/flying-press.php'                                                 => 'HMWP_Models_Compatibility_FlyingPress',
			'two-factor/two-factor.php'                                                     => 'HMWP_Models_Compatibility_TwoFactor',
			'hcaptcha-for-forms-and-more/hcaptcha.php'                                      => 'HMWP_Models_Compatibility_hCaptcha',
			'mainwp-child/mainwp-child.php'                                                 => 'HMWP_Models_Compatibility_MainWP',
			'elementor/elementor.php'                                                       => 'HMWP_Models_Compatibility_Elementor',
			'userswp/userswp.php'                                                           => 'HMWP_Models_Compatibility_UsersWP',
			'litespeed-cache/litespeed-cache.php'                                           => 'HMWP_Models_Compatibility_LiteSpeed',
			'wp-social-pro/wp-social-pro.php'                                               => 'HMWP_Models_Compatibility_WPSocial',
		);

		try {

			foreach ( $compatibilities as $plugin => $class ) {
				if ( HMWP_Classes_Tools::isPluginActive( $plugin ) ) {
					HMWP_Classes_ObjController::getClass( $class );
				}
			}

		} catch ( Exception $e ) {
		}

		//Refresh rewrites when a new website or new term is created on Litespeed server
		if ( HMWP_Classes_Tools::isLitespeed() ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility_LiteSpeed' );
		}

		//Compatibility with More plugin
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility_Others' );

	}

	/**
	 * Check other plugins and set compatibility settings
	 *
	 * @throws Exception
	 */
	public function checkBuildersCompatibility() {

		//Check the compatibility with builders
		//Don't load when on builder editor
		//Compatibility with Oxygen Plugin, Elementor, Thrive and more, Yellow Pencil, Wp Bakery
		if ( HMWP_Classes_Tools::isLoggedInUser() ) {
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
				'breakdance',//Breakdance plugin
				'breakdance_iframe',//Breakdance plugin
				'np_edit',//Nicepage plugin
				'np_new',//Nicepage plugin
			);

			foreach ( $builder_paramas as $param ) {
				if ( HMWP_Classes_Tools::getIsset( $param ) ) {
					//Stop WP Ghost from loading while on editor
					add_filter( 'hmwp_start_buffer', '__return_false' );
					add_filter( 'hmwp_process_buffer', '__return_false' );
					add_filter( 'hmwp_process_hide_disable', '__return_false' );
					add_filter( 'hmwp_process_find_replace', '__return_false' );

					return true;
				}
			}

		}

		return false;

	}

	/**
	 * Check if the cache plugins are loaded and have cached files
	 *
	 * @throws Exception
	 */
	public function checkCacheFiles() {
		global $wpdb;
		$changed = false;

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();
		$content_dir   = $wp_filesystem->wp_content_dir();

		//If the plugin is not set to map all the files dynamically
		if ( ! HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ) ) {
			//Change the paths in the elementor cached css
			if ( HMWP_Classes_Tools::isPluginActive( 'elementor/elementor.php' ) ) {
				if ( HMWP_Classes_Tools::isMultisites() ) {

					if ( $blogs = $wpdb->get_results( $wpdb->prepare( 'SELECT blog_id FROM `' . esc_sql( $wpdb->blogs ) . '` WHERE blog_id > %d', 1 ) ) ) { //phpcs:ignore
						foreach ( $blogs as $blog ) {

							//Set the cache directory for this plugin
							$path = $content_dir . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/sites/' . $blog->blog_id . '/elementor/css/';

							if ( $wp_filesystem->is_dir( $path ) ) {

								//Set the cache directory for this plugin
								HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

								//change the paths in css
								HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();

								//mark as cache changed
								$changed = true;
							}

							//Set the cache directory for this plugin
							$path = $content_dir . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/sites/' . $blog->blog_id . '/elementor/google-fonts/';
							if ( $wp_filesystem->is_dir( $path ) ) {
								HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

								//change the paths in css
								HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();

								//mark as cache changed
								$changed = true;
							}
						}
					}
				} else {
					//Set the cache directory for this plugin
					$path = $content_dir . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/elementor/css/';
					if ( $wp_filesystem->is_dir( $path ) ) {
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

						//change the paths in css
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();

						//mark as cache changed
						$changed = true;
					}
					//Set the cache directory for this plugin
					$path = $content_dir . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/elementor/google-fonts/';
					if ( $wp_filesystem->is_dir( $path ) ) {
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

						//change the paths in css
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();

						//mark as cache changed
						$changed = true;
					}
				}

			}
		}

		//Change the paths in the cached css
		if ( HMWP_Classes_Tools::isPluginActive( 'fusion-builder/fusion-builder.php' ) ) {
			//Set the cache directory for this plugin
			$path = $content_dir . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/fusion-styles/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//mark as cache changed
				$changed = true;
			}
		}

		//Change the paths in the cached css
		if ( HMWP_Classes_Tools::isPluginActive( 'beaver-builder-lite-version/fl-builder.php' ) || HMWP_Classes_Tools::isPluginActive( 'beaver-builder/fl-builder.php' ) ) {
			//Set the cache directory for this plugin
			$path = $content_dir . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/bb-plugin/cache/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

				//mark as cache changed
				$changed = true;
			}
		}

		//Change the paths in the cached css
		if ( HMWP_Classes_Tools::isPluginActive( 'wp-super-cache/wp-cache.php' ) ) {

			//Initialize WordPress Filesystem
			$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

			$wp_cache_config_file = $content_dir . 'wp-cache-config.php';

			if ( $wp_filesystem->exists( $wp_cache_config_file ) ) {
				include $wp_cache_config_file;
			}

			//Set the cache directory for this plugin
			if ( isset( $cache_path ) ) {
				$path = $cache_path;
			} else {
				$path = $content_dir . 'cache';
			}

			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

				//mark as cache changed
				$changed = true;
			}
		}

		//Change the paths in the cached css
		if ( HMWP_Classes_Tools::isPluginActive( 'litespeed-cache/litespeed-cache.php' ) ) {

			//Set the cache directory for this plugin
			$path = $content_dir . 'litespeed/';

			//if set by the plugin, them select the defined folder
			if( defined('LITESPEED_DATA_FOLDER') && LITESPEED_DATA_FOLDER <> ''){
				$path = $content_dir . LITESPEED_DATA_FOLDER . '/';
			}

			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

				//mark as cache changed
				$changed = true;
			}
		}

		//Change the paths in the cached css
		if ( HMWP_Classes_Tools::isPluginActive( 'comet-cache/comet-cache.php' ) ) {

			//Set the cache directory for this plugin
			$path = false;
			if ( $options = get_option( 'comet_cache_options' ) ) {
				if ( isset( $options['base_dir'] ) ) {
					$path = $content_dir . trim( $options['base_dir'], '/' ) . '/';
				}
			}

			if ( ! $path ) {
				$path = $content_dir . 'cache/';
			}

			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

				//mark as cache changed
				$changed = true;
			}
		}

		if ( HMWP_Classes_Tools::isPluginActive( 'hummingbird-performance/wp-hummingbird.php' ) ) {
			//Set the cache directory for this plugin
			$path = $content_dir . 'wphb-cache/';

			if ( $options = get_option( 'wphb_settings' ) ) {
				if ( isset( $options['minify']['file_path'] ) ) {
					$path = $wp_filesystem->abspath() . trim( $options['minify']['file_path'], '/' ) . '/';
				}
			}

			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

				//mark as cache changed
				$changed = true;
			}
		}

		if ( HMWP_Classes_Tools::isPluginActive( 'hyper-cache/plugin.php' ) ) {
			//Set the cache directory for this plugin
			if ( defined( 'HYPER_CACHE_FOLDER' ) ) {
				$path = HYPER_CACHE_FOLDER;
			} else {
				$path = $content_dir . 'cache/';
			}

			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();
				//change the paths in html
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInHTML();

				//mark as cache changed
				$changed = true;
			}
		}

		//For WP-Rocket
		if ( HMWP_Classes_Tools::isPluginActive( 'wp-rocket/wp-rocket.php' ) ) {
			if ( function_exists( 'get_rocket_option' ) ) {

				if ( get_rocket_option( 'minify_concatenate_css' ) && defined( 'WP_ROCKET_MINIFY_CACHE_PATH' ) ) {

					if ( HMWP_Classes_Tools::isMultisites() ) {

						if ( $blogs = $wpdb->get_results( $wpdb->prepare( 'SELECT blog_id FROM `' . esc_sql( $wpdb->blogs ) . '` WHERE blog_id > %d', 1 ) ) ) { //phpcs:ignore
							foreach ( $blogs as $blog ) {

								//Set the cache directory for this plugin
								$path = WP_ROCKET_MINIFY_CACHE_PATH . $blog->blog_id . '/';

								if ( $wp_filesystem->is_dir( $path ) ) {

									HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

									//change the paths in css
									HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
									//change the paths in js
									HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

									//mark as cache changed
									$changed = true;
								}
							}
						}
					}

					//Set the cache directory for this plugin
					$path = WP_ROCKET_MINIFY_CACHE_PATH . get_current_blog_id() . '/';

					if ( $wp_filesystem->is_dir( $path ) ) {
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

						//change the paths in css
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
						//change the paths in js
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

						//mark as cache changed
						$changed = true;
					}
				}
			}


		}

		//For Autoptimizer
		if ( HMWP_Classes_Tools::isPluginActive( 'autoptimize/autoptimize.php' ) ) {
			//Set the cache directory for this plugin
			$path = $content_dir . 'cache/autoptimize/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();
				//mark as cache changed
				$changed = true;
			}
		}

		if ( HMWP_Classes_Tools::isPluginActive( 'wp-core-web-vitals/wpcorewebvitals.php' ) ) {
			//Set the cache directory for this plugin
			$path = $content_dir . 'cache/wp_cwv/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();
				//Change the paths in html
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInHTML();
				//mark as cache changed
				$changed = true;
			}
		}


		//For bb-plugin
		if ( HMWP_Classes_Tools::isPluginActive( 'beaver-builder-lite-version/fl-builder.php' ) ) {
			//Set the cache directory for this plugin
			$path = $content_dir . 'uploads/bb-plugin/cache/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

				//mark as cache changed
				$changed = true;
			}
		}

		if ( ( HMWP_Classes_Tools::isPluginActive( 'swift-performance/performance.php' ) || HMWP_Classes_Tools::isPluginActive( 'swift-performance-lite/performance.php' ) ) && defined( 'SWIFT_PERFORMANCE_CACHE_DIR' ) ) {
			//Set the cache directory for this plugin
			$path = SWIFT_PERFORMANCE_CACHE_DIR;
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//change the paths in css
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();
				//mark as cache changed
				$changed = true;
			}
		}


		//For WP Fastest Cache
		if ( HMWP_Classes_Tools::isPluginActive( 'wp-fastest-cache/wpFastestCache.php' ) ) {
			//Set the cache directory for this plugin
			$path = $content_dir . 'cache/wpfc-minified/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//Change the paths in cache
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();
				//Change the paths in html
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInHTML();
			}

			$path = $content_dir . 'cache/all/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//Change the paths in cache
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInHTML();

				//mark as cache changed
				$changed = true;
			}
		}

		//For Siteground Cache
		if ( HMWP_Classes_Tools::isPluginActive( 'sg-cachepress/sg-cachepress.php' ) ) {

			if ( HMWP_Classes_Tools::isMultisites() ) {

				if ( $blogs = $wpdb->get_results( $wpdb->prepare( 'SELECT blog_id FROM `' . esc_sql( $wpdb->blogs ) . '` WHERE blog_id > %d', 1 ) ) ) { //phpcs:ignore
					foreach ( $blogs as $blog ) {
						//Set the cache directory for this plugin
						$path = $content_dir . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/sites/' . $blog->blog_id . '/siteground-optimizer-assets/';
						if ( $wp_filesystem->is_dir( $path ) ) {
							HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );
							//Change the paths in cache
							HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
							//change the paths in js
							HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

							//mark as cache changed
							$changed = true;
						}
					}
				}
			} else {
				//Set the cache directory for this plugin
				$path = $content_dir . HMWP_Classes_Tools::getDefault( 'hmwp_upload_url' ) . '/siteground-optimizer-assets/';
				if ( $wp_filesystem->is_dir( $path ) ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );
					//Change the paths in cache
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
					//change the paths in js
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

					//mark as cache changed
					$changed = true;
				}
			}

			//Set the cache directory for this plugin
			$path = $content_dir . 'cache/sgo-cache/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );
				//Change the paths in cache
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInHTML();
				//mark as cache changed
				$changed = true;
			}
		}

		//For JCH Optimize Cache
		if ( HMWP_Classes_Tools::isPluginActive( 'jch-optimize/jch-optimize.php' ) ) {
			//Change the paths in css
			$path = $content_dir . 'cache/jch-optimize/css/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();

				//mark as cache changed
				$changed = true;
			}

			//change the paths in js
			$path = $content_dir . 'cache/jch-optimize/js/';
			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

				//mark as cache changed
				$changed = true;
			}

		}

		//IF none of these plugins are installed. Search whole directory.
		if ( ! $changed || HMWP_Classes_Tools::getOption( 'hmwp_change_in_cache_directory' ) <> '' ) {
			//Set the cache directory for this plugin
			if ( HMWP_Classes_Tools::getOption( 'hmwp_change_in_cache_directory' ) <> '' ) {
				$path = $content_dir . trim( HMWP_Classes_Tools::getOption( 'hmwp_change_in_cache_directory' ), '/' ) . '/';
			} else {
				$path = $content_dir . 'cache/';
			}

			if ( $wp_filesystem->is_dir( $path ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->setCachePath( $path );

				//if other cache plugins are installed
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInCss();
				//change the paths in js
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cache' )->changePathsInJs();

				//mark as cache changed
				$changed = true;
			}
		}

		if ( $changed && isset( $path ) ) {
			//For debugging
			do_action( 'hmwp_debug_cache', $path );
		}

	}

	/**
	 * Get all alert messages
	 *
	 * @throws Exception
	 */
	public static function getAlerts() {

		// First thing you need to do
		$page = HMWP_Classes_Tools::getValue( 'page' );
		if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' && $page == 'hmwp_settings' ) {
			/* translators: 1: Opening <a><strong> tag to settings page, 2: Closing </strong></a> tag, 3: Opening <a><strong> tag to settings page, 4: Closing </strong></a> tag, 5: Plugin name wrapped in <strong> tags. */
			HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'First, you need to activate the %1$sLite Mode%2$s or %3$sGhost Mode%4$s in %5$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '"><strong>', '</strong></a>', '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '"><strong>', '</strong></a>', '<strong>' . esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ) . '</strong>' ) ) );
		}

		// Check if the Geo Country database is downloaded correctly
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_GeoLocator' )->getDatabaseFilename();

		if ( ( HMWP_Classes_Tools::getOption( 'hmwp_activity_log' ) || HMWP_Classes_Tools::getOption( 'hmwp_threats_log' ) )&& ! file_exists( _HMWP_MODEL_DIR_ . 'geoip/' . $database ) ) {
			$restoreLink = '<a href="' . esc_url( add_query_arg( array( 'hmwp_nonce' => wp_create_nonce( 'hmwp_geo_download' ), 'action' => 'hmwp_geo_download' ) ) ) . '" class="btn btn-default btn-sm ml-3" />' . esc_html__( "Click to retry the download", 'hide-my-wp' ) . '</a>';
			HMWP_Classes_Error::setNotification( esc_html__( 'The Country database has not been downloaded, preventing the User Events Log and Security Threats Log features from working properly.', 'hide-my-wp' ) . $restoreLink );
		}

		// Announce the plugin name change
		if( HMWP_Classes_Tools::getOption('hmwp_plugin_name') == 'Hide My WP Ghost' && $page == 'hmwp_settings' ){
			$link = '<a href="' . esc_url(add_query_arg( array( 'hmwp_nonce' => wp_create_nonce( 'hmwp_update_product_name' ), 'action' => 'hmwp_update_product_name' ) )) . '" class="btn btn-default btn-small py-1 ml-2" style="font-size: 14px;" />' . esc_html__( "Update Now", 'hide-my-wp' ) . '</a>';
			/* translators: 1: Opening <strong> tag, 2: Closing </strong> tag, 3: Opening <strong> tag, 4: Closing </strong> tag, 5: Opening <strong> tag, 6: Closing </strong> tag, 7: Update link HTML. */
			HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( '%1$s Good News! %2$s You can now update the plugin name from %3$s Hide My WP Ghost %4$s to %5$s WP Ghost %6$s on your website! %7$s', 'hide-my-wp' ), '<strong>', '</strong>', '<strong>', '</strong>', '<strong>', '</strong>', $link ) ) );
		}

		//is CDN plugin installed
		if ( is_admin() || is_network_admin() ) {
			if ( HMWP_Classes_Tools::isPluginActive( 'cdn-enabler/cdn-enabler.php' ) ) {
				if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) <> 'default' ) {
					if ( $cdn_enabler = get_option( 'cdn_enabler' ) ) {
						if ( isset( $cdn_enabler['dirs'] ) ) {
							$dirs = explode( ',', $cdn_enabler['dirs'] );
							if ( ! empty( $dirs ) && ! in_array( HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ), $dirs ) && ! in_array( HMWP_Classes_Tools::getOption( 'hmwp_wp-includes_url' ), $dirs ) ) {
								/* translators: 1: wp-content URL wrapped in <strong> tags, 2: wp-includes URL wrapped in <strong> tags. */
								HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'CDN Enabled detected. Please include %1$s and %2$s paths in CDN Enabler Settings', 'hide-my-wp' ), '<strong>' . esc_html( HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ) ) . '</strong>', '<strong>' . esc_html( HMWP_Classes_Tools::getOption( 'hmwp_wp-includes_url' ) ) . '</strong>' ) ) );
							}
						}
					}
				}

				if ( isset( $_SERVER['REQUEST_URI'] ) && admin_url( 'options-general.php?page=cdn_enabler', 'relative' ) == $_SERVER['REQUEST_URI'] ) {
					/* translators: 1: Plugin name, 2: Opening <a> tag to CDN Enabler tutorial URL, 3: Closing </a> tag. */
					HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'CDN Enabler detected! Learn how to configure it with %1$s %2$sClick here%3$s', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/wp-ghost-and-cdn-enabler/' ) . '" target="_blank">', '</a>' ) ) );
				}
			}

			//Compatibility with WP Cache plugin for CDN list
			if ( HMWP_Classes_Tools::isPluginActive( 'wp-super-cache/wp-cache.php' ) ) {
				if ( get_option( 'ossdl_off_cdn_url' ) <> '' && get_option( 'ossdl_off_cdn_url' ) <> home_url() ) {
					$dirs = explode( ',', get_option( 'ossdl_off_include_dirs' ) );
					if ( ! empty( $dirs ) && ! in_array( HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ), $dirs ) && ! in_array( HMWP_Classes_Tools::getOption( 'hmwp_wp-includes_url' ), $dirs ) ) {
						/* translators: 1: wp-content URL wrapped in <strong> tags, 2: wp-includes URL wrapped in <strong> tags. */
						HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'WP Super Cache CDN detected. Please include %1$s and %2$s paths in WP Super Cache > CDN > Include directories', 'hide-my-wp' ), '<strong>' . esc_html( HMWP_Classes_Tools::getOption( 'hmwp_wp-content_url' ) ) . '</strong>', '<strong>' . esc_html( HMWP_Classes_Tools::getOption( 'hmwp_wp-includes_url' ) ) . '</strong>' ) ) );
					}
				}
			}

			//Admin Ajax alert for Affiliate Pro plugin
			if ( HMWP_Classes_Tools::isPluginActive( 'indeed-affiliate-pro/indeed-affiliate-pro.php' ) ) {
				if ( HMWP_Classes_Tools::getOption( 'hmwp_admin-ajax_url' ) <> HMWP_Classes_Tools::getDefault( 'hmwp_admin-ajax_url' ) ) {
					/* translators: 1: admin-ajax.php wrapped in <strong> tags. */
					HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'Ultimate Affiliate Pro detected. The plugin doesn\'t support custom %1$s paths as it doesn\'t use WordPress functions to call the Ajax URL', 'hide-my-wp' ), '<strong>admin-ajax.php</strong>' ) ) );
				}
			}

			//Admin Ajax alert for Membership Pro plugin
			if ( HMWP_Classes_Tools::isPluginActive( 'indeed-membership-pro/indeed-membership-pro.php' ) ) {
				if ( HMWP_Classes_Tools::getOption( 'hmwp_admin-ajax_url' ) <> HMWP_Classes_Tools::getDefault( 'hmwp_admin-ajax_url' ) ) {
					/* translators: 1: admin-ajax.php wrapped in <strong> tags. */
					HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'Indeed Ultimate Membership Pro detected. The plugin doesn\'t support custom %1$s paths as it doesn\'t use WordPress functions to call the Ajax URL', 'hide-my-wp' ), '<strong>admin-ajax.php</strong>' ) ) );
				}
			}

			//Mor Rewrite is not installed
			if ( HMWP_Classes_Tools::isApache() && ! HMWP_Classes_Tools::isModeRewrite() ) {
				/* translators: 1: Plugin name, 2: Opening <a> tag to mod_rewrite tutorial URL, 3: Closing </a> tag. */
				HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( '%1$s does not work without mode_rewrite. Please activate the rewrite module in Apache. %2$sMore details%3$s', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ), '<a href="' . esc_url( 'https://tecadmin.net/enable-apache-mod-rewrite-module-in-ubuntu-linuxmint/' ) . '" target="_blank">', '</a>' ) ) );
			}

			//IIS server and no Rewrite Permalinks installed
			if ( HMWP_Classes_Tools::isIIS() && HMWP_Classes_Tools::isPHPPermalink() ) {
				/* translators: 1: Opening <a> tag to IIS URL Rewrite page, 2: Closing </a> tag. */
				HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'You need to activate the URL Rewrite for IIS to be able to change the permalink structure to friendly URL (without index.php). %1$sMore details%2$s', 'hide-my-wp' ), '<a href="' . esc_url( 'https://www.iis.net/downloads/microsoft/url-rewrite' ) . '" target="_blank">', '</a>' ) ) );
			} elseif ( HMWP_Classes_Tools::isPHPPermalink() ) {
				HMWP_Classes_Error::setNotification( esc_html__( 'You need to set the permalink structure to friendly URL (without index.php).', 'hide-my-wp' ) );
			}

			//Inmotion server detected
			if ( HMWP_Classes_Tools::isInmotion() && HMWP_Classes_Tools::isNginx() ) {
				/* translators: 1: Opening <a> tag to Inmotion tutorial URL, 2: Closing </a> tag. */
				HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'Inmotion detected. %1$sPlease read how to make the plugin compatible with Inmotion Nginx Cache%2$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/inmotion-wordpress-hosting-wp-ghost-setup/' ) . '" target="_blank">', '</a>' ) ) );
			}

			if ( HMWP_Classes_Tools::isAWS() ) {
				/* translators: 1: Opening <a> tag to Bitnami tutorial URL, 2: Closing </a> tag. */
				HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'Bitnami detected. %1$sPlease read how to make the plugin compatible with AWS hosting%2$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/bitnami-servers-wp-ghost-setup/' ) . '" target="_blank">', '</a>' ) ) );
			}

			if ( HMWP_Classes_Tools::isCloudPanel() ) {
				/* translators: 1: Opening <a> tag to Cloud Panel tutorial URL, 2: Closing </a> tag. */
				HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'Cloud Panel detected. %1$sPlease read how to make the plugin compatible with Cloud Panel hosting%2$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/cloud-panel-server-wp-ghost-setup/' ) . '" target="_blank">', '</a>' ) ) );
			}

			//The login path is changed by other plugins and may affect the functionality
			if ( HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) == HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) ) {
				if ( strpos( site_url( 'wp-login.php' ), HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) ) === false ) {
					defined( 'HMWP_DEFAULT_LOGIN' ) || define( 'HMWP_DEFAULT_LOGIN', site_url( 'wp-login.php' ) );
				}
			}

			if ( HMWP_Classes_Tools::isThemeActive( 'Avada' ) ) {
				if ( ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) <> 'default' ) ) {
					if ( HMWP_Classes_Tools::getOption( 'hmwp_themes_url' ) <> HMWP_Classes_Tools::getDefault( 'hmwp_themes_url' ) ) {

						$avada_path = $fusion_url = false;
						$themes     = HMWP_Classes_Tools::getOption( 'hmwp_themes' );

						foreach ( $themes['from'] as $index => $theme ) {
							if ( strpos( $theme, 'Avada' ) !== false ) {
								$avada_path = trim( $themes['to'][ $index ], '/' );
							}
						}

						if ( $avada_path && $avada_path <> 'Avada' ) {
							$fusion_url = site_url( HMWP_Classes_Tools::getOption( 'hmwp_themes_url' ) ) . '/' . $avada_path . '/includes/lib';
						}

						if ( $fusion_url ) {
							if ( defined( 'FUSION_LIBRARY_URL' ) && stripos( FUSION_LIBRARY_URL, $fusion_url ) === false ) {
								/* translators: 1: Code snippet wrapped in <br /><strong> tags. */
								HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'To hide the Avada library, please add the Avada FUSION_LIBRARY_URL in wp-config.php file after $table_prefix line: %1$s', 'hide-my-wp' ), '<br /><strong>define(\'FUSION_LIBRARY_URL\',\'' . esc_url( site_url( HMWP_Classes_Tools::getOption( 'hmwp_themes_url' ) ) . '/' . $avada_path . '/includes/lib' ) . '\');</strong>' ) ) );							}
						}


					}

				}
			}

			//The admin URL is already changed by other plugins and may affect the functionality
			if ( ! HMW_RULES_IN_CONFIG ) {
				/* translators: 1: Plugin name. */
				HMWP_Classes_Error::setNotification( sprintf( esc_html__( '%1$s rules are not saved in the config file and this may affect the website loading speed.', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ) ) );
				defined( 'HMWP_DEFAULT_ADMIN' ) || define( 'HMWP_DEFAULT_ADMIN', HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) );
			} elseif ( HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) == HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) ) {
				if ( strpos( admin_url(), HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) ) === false ) {
					defined( 'HMWP_DEFAULT_ADMIN' ) || define( 'HMWP_DEFAULT_ADMIN', admin_url() );
				}
			}

			if ( HMWP_Classes_Tools::isGodaddy() ) {
				/* translators: 1: Opening <strong><a> tag with GoDaddy instructions link, 2: Closing </a></strong> tag. */
				HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'Godaddy detected! To avoid CSS errors, make sure you switch off the CDN from %1$sGodaddy > Managed WordPress > Overview%2$s', 'hide-my-wp' ), '<strong><a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/godaddy-hosting-wp-ghost-setup/' ) . '" target="_blank">', '</a></strong>' ) ) );
			}

			if ( HMWP_Classes_Tools::isPluginActive( 'bulletproof-security/bulletproof-security.php' ) ) {
				/* translators: 1: Plugin name. */
				HMWP_Classes_Error::setNotification( sprintf( esc_html__( 'BulletProof plugin! Make sure you save the settings in %1$s after activating Root Folder BulletProof Mode in BulletProof plugin.', 'hide-my-wp' ), esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ) ) ) );
			}

			if ( HMWP_Classes_Tools::isPluginActive( 'worker/init.php' ) && ! HMWP_Classes_Tools::getOption( 'hmwp_firstload' ) ) {
				/* translators: 1: Opening <a> tag to Compatibility settings URL, 2: Closing </a> tag. */
				HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'Activate "Must Use Plugin Loading" from "Plugin Loading Hook" to be able to connect to your dashboard directly from managewp.com. %1$s click here %2$s', 'hide-my-wp' ), '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_advanced&tab=compatibility', true ) ) . '" >', '</a>' ) ) );
			}

			//Check if the rules are working as expected
			$mappings = HMWP_Classes_Tools::getOption( 'file_mappings' );
			if ( ! empty( $mappings ) ) {

				//Detect if any of the unrewritten files is a layout asset (css/js/font/svg).
				//These break the frontend design, while images only slow down the website.
				$breaks_layout = (bool) preg_grep( '/\.(css|js|svg|woff2?|ttf|eot)(\?|$)/i', (array) $mappings );

				$checkLink   = '<a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_permalinks' ) ) . '" class="btn btn-default btn-sm mt-3 mr-2">' . esc_html__( "Run Frontend Check", 'hide-my-wp' ) . '</a>';
				$restoreLink = '<a href="' . esc_url( add_query_arg( array( 'hmwp_nonce' => wp_create_nonce( 'hmwp_ignore_errors' ), 'action'     => 'hmwp_ignore_errors' ) ) ) . '" class="btn btn-default btn-sm mt-3" />' . esc_html__( "Close Error", 'hide-my-wp' ) . '</a>';

				$kbLink = '<a href="' . esc_url( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) . '/kb/theme-not-loading-correctly-website-loads-slower/' ) . '" target="_blank" class="text-warning">' . esc_html( HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' ) ) . '/kb/theme-not-loading-correctly-website-loads-slower/</a>';

				if ( $breaks_layout ) {
					/* translators: 1: Line breaks and the list of affected file URLs, 2: Tutorial link HTML followed by the action buttons. */
					$message = sprintf( __( 'Attention! Your frontend layout may not be loading correctly. Some theme files (CSS/JS) passed through the config file rules and were loaded through WordPress rewrite instead, which can break the website design. %1$s Please follow this tutorial to fix the issue: %2$s', 'hide-my-wp' ), '<br /><br />' . join( '<br />', $mappings ) . '<br /><br />', $kbLink . '<br />' . $checkLink . ' ' . $restoreLink );
				} else {
					/* translators: 1: Line breaks and the list of affected file URLs, 2: Tutorial link HTML followed by the action buttons. */
					$message = sprintf( __( 'Attention! Some URLs passed through the config file rules and were loaded through WordPress rewrite which may slow down your website. %1$s Please follow this tutorial to fix the issue: %2$s', 'hide-my-wp' ), '<br /><br />' . join( '<br />', $mappings ) . '<br /><br />', $kbLink . '<br />' . $checkLink . ' ' . $restoreLink );
				}

				HMWP_Classes_Error::setNotification( wp_kses_post( $message ), 'text-white bg-danger' );
			}

			if ( HMWP_Classes_Tools::isPluginActive( 'ultimate-member/ultimate-member.php' ) && HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) && HMWP_Classes_Tools::getOption( 'brute_use_captcha_v3' ) ) {
				/* translators: 1: Plugin name. */
				HMWP_Classes_Error::setNotification( sprintf( esc_html__( 'Google reCaptcha V3 is not working with the current login form of %1$s.', 'hide-my-wp' ), esc_html( 'Ultimate Member plugin' ) ) );
			}

			if ( HMWP_Classes_Tools::isPluginActive( 'wp-user-manager/wp-user-manager.php' ) && HMWP_Classes_Tools::getOption( 'hmwp_bruteforce' ) && HMWP_Classes_Tools::getOption( 'brute_use_captcha_v3' ) ) {
				/* translators: 1: Plugin name. */
				HMWP_Classes_Error::setNotification( sprintf( esc_html__( 'Google reCaptcha V3 is not working with the current login form of %1$s.', 'hide-my-wp' ), esc_html( 'Ultimate Member plugin' ) ) );
			}

			if ( ( ! HMWP_Classes_Tools::getOption('hmwp_sqlinjection') || HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') < 3 ) && HMWP_Classes_Tools::getOption('hmwp_threats_log') && $page == 'hmwp_log' ) {
				/* translators: 1: Line break and opening <strong><a> tag to Firewall settings URL, 2: Closing </a></strong> tag. */
				HMWP_Classes_Error::setNotification( wp_kses_post( sprintf( __( 'Your site is currently logging security threats, but advanced firewall filtering is not fully enabled. %1$s Activate 7G or 8G Firewall %2$s to automatically block malicious patterns before they reach WordPress.', 'hide-my-wp' ), '<br /><strong><a href="' . esc_url( HMWP_Classes_Tools::getSettingsUrl( 'hmwp_firewall&tab=firewall', true ) ) . '" >', '</a></strong>' ) ) );
			}


		}

	}

	/**
	 * Include CDNs if found
	 *
	 * @return array|false
	 */
	public function findCDNServers() {
		$cdn_urls = array();

		//If WP_CONTENT_URL is set as a different domain
		if ( defined( 'WP_CONTENT_URL' ) && WP_CONTENT_URL <> '' ) {
			$cdn    = wp_parse_url( WP_CONTENT_URL, PHP_URL_HOST );
			$domain = wp_parse_url( home_url(), PHP_URL_HOST );

			if ( $cdn <> '' && $domain <> '' && $cdn <> $domain ) {
				$cdn_urls[] = WP_CONTENT_URL;
			}
		}

		//WP Rocket CDN Integration
		if ( HMWP_Classes_Tools::isPluginActive( 'wp-rocket/wp-rocket.php' ) && function_exists( 'get_rocket_option' ) ) {
			$cnames = get_rocket_option( 'cdn_cnames', array() );
			foreach ( $cnames as $_urls ) {

				$_urls = explode( ',', $_urls );
				$_urls = array_map( 'trim', $_urls );

				foreach ( $_urls as $url ) {
					$cdn_urls[] = $url;
				}
			}
		}

		//CDN Enabler Integration
		if ( HMWP_Classes_Tools::isPluginActive( 'cdn-enabler/cdn-enabler.php' ) ) {
			if ( $cdn_enabler = get_option( 'cdn_enabler' ) ) {
				if ( isset( $cdn_enabler['url'] ) ) {
					$url = $cdn_enabler['url'];
					$cdn_urls[] = $url;
				}
			}
		}

		//Power Cache CDN integration
		if ( HMWP_Classes_Tools::isPluginActive( 'powered-cache/powered-cache.php' ) ) {
			global $powered_cache_options;
			if ( isset( $powered_cache_options['cdn_hostname'] ) ) {
				$hostnames = $powered_cache_options['cdn_hostname'];
				if ( ! empty( $hostnames ) ) {
					foreach ( $hostnames as $host ) {
						if ( ! empty( $host ) ) {
							$cdn_urls[] = $host;
						}
					}
				}
			}
		}

		//Wp Cache CDN integration
		if ( HMWP_Classes_Tools::isPluginActive( 'wp-super-cache/wp-cache.php' ) ) {
			if ( get_option( 'ossdl_off_cdn_url' ) <> '' && get_option( 'ossdl_off_cdn_url' ) <> home_url() ) {
				$url = get_option( 'ossdl_off_cdn_url' );
				$cdn_urls[] = $url;
			}
		}

		//Ewww plugin CDN
		if ( HMWP_Classes_Tools::isPluginActive( 'ewww-image-optimizer/ewww-image-optimizer.php' ) ) {
			$domain = get_option( 'ewww_image_optimizer_exactdn_domain', false );
			if ( $domain ) {
				$cdn_urls[] = $domain;
			}
		}

		//JCH Optimize CDN integration
		if ( HMWP_Classes_Tools::isPluginActive( 'jch-optimize/jch-optimize.php' ) ) {
			if ( $jch = get_option( 'jch_options' ) ) {
				if ( is_array( $jch ) ) {
					if ( isset( $jch['cookielessdomain_enable'] ) && $jch['cookielessdomain_enable'] && isset( $jch['cookielessdomain'] ) && $jch['cookielessdomain'] <> '' ) {
						$cdn_urls[] = $jch['cookielessdomain'];
					}
				}
			}
		}

		//Hyper Cache CDN integration
		if ( HMWP_Classes_Tools::isPluginActive( 'hyper-cache/plugin.php' ) ) {
			if ( $cdn = get_option( 'hyper-cache' ) ) {
				if ( isset( $cdn['cdn_enabled'] ) && $cdn['cdn_enabled'] && isset( $cdn['cdn_url'] ) && $cdn['cdn_url'] ) {
					$url = $cdn['cdn_url'];
					$cdn_urls[] = $url;
				}
			}
		}

		//Bunny CDN integration
		if ( HMWP_Classes_Tools::isPluginActive( 'bunnycdn/bunnycdn.php' ) ) {
			if ( $bunnycdn = get_option( 'bunnycdn' ) ) {
				if ( isset( $bunnycdn['cdn_domain_name'] ) && $bunnycdn['cdn_domain_name'] ) {
					$cdn_urls[] = $bunnycdn['cdn_domain_name'];
				}
			}
		}

		//get plugin DB CDN list
		$hmwp_cdn_urls = json_decode( HMWP_Classes_Tools::getOption( 'hmwp_cdn_urls' ), true );
		if ( ! empty( $hmwp_cdn_urls ) ) {
			foreach ( $hmwp_cdn_urls as $url ) {
				$cdn_urls[] = $url;
			}
		}

		if ( ! empty( $cdn_urls ) ) {
			return array_unique( $cdn_urls );
		}

		return false;
	}


	/************************************************************
	 *
	 * Must Use Plugin (needed for Manage WP and other cache plugins)
	 */

	/**
	 * Add the Must-Use plugin to make sure is loading for the custom wp-admin path every time
	 */
	public function addMUPlugin() {
		try {
			$this->registerMUPlugin( '0-hidemywp.php', $this->buildLoaderContent( 'hide-my-wp/index.php' ) );
		} catch ( Exception $e ) {
		}
	}

	/**
	 * Remove the Must-Use plugin on deactivation
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
	 * @param  $pluginBasename
	 *
	 * @return string
	 */
	public function buildLoaderContent( $pluginBasename ) {
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
	public function registerMUPlugin( $loaderName, $loaderContent ) {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		$mustUsePluginDir = rtrim( WPMU_PLUGIN_DIR, '/' );
		$loaderPath       = $mustUsePluginDir . '/' . $loaderName;

		if ( $wp_filesystem->exists( $loaderPath ) && md5( $loaderContent ) === md5_file( $loaderPath ) ) {
			return;
		}

		if ( ! $wp_filesystem->is_dir( $mustUsePluginDir ) ) {
			$wp_filesystem->mkdir( $mustUsePluginDir );
		}

		if ( $wp_filesystem->is_writable( $mustUsePluginDir ) ) {
			$wp_filesystem->put_contents( $loaderPath, $loaderContent );
		}

	}

	/**
	 * Delete the MU file
	 *
	 * @param $loaderName
	 */
	public function deregisterMUPlugin( $loaderName ) {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		$mustUsePluginDir = rtrim( WPMU_PLUGIN_DIR, '/' );
		$loaderPath       = $mustUsePluginDir . '/' . $loaderName;

		if ( ! $wp_filesystem->exists( $loaderPath ) ) {
			return;
		}

		$wp_filesystem->delete( $loaderPath );
	}


}
