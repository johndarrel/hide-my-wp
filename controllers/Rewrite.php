<?php
/**
 * Handle all the redirects and hidden paths
 * Rewrite Class
 *
 * @file The Rewrites file
 * @package HMWP/Rewrite
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Controllers_Rewrite extends HMWP_Classes_FrontController {
	/**
	 * HMWP_Controllers_Rewrite constructor.
	 *
	 * @throws Exception
	 */
	public function __construct() {
		parent::__construct();

		// If the plugin is set to be deactivated, return
		if ( defined( 'HMWP_DISABLE' ) && HMWP_DISABLE ) {
			return;
		}

		// If doing cron, return
		if ( HMWP_Classes_Tools::isCron() ) {
			return;
		}

		// If plugin paused from plugins
		if ( get_transient( 'hmwp_disable' ) ) {
			return;
		}

		// Init the main hooks
		// Start HMWP path process
		$this->initHooks();

	}

	/**
	 * Initializes the main hooks and processes for the application, including URL modifications,
	 * access control, and compatibility checks.
	 *
	 * This method sets up a series of filters and actions to control the URL paths, login redirects, and
	 * firewall checks. It also ensures proper handling of URL rewriting and redirections, depending on
	 * specific settings and configurations.
	 *
	 * @return void
	 *
	 * @throws Exception If an unexpected condition is encountered during initialization.
	 */
	public function initHooks() {

		// Stop here is the option is default.
		// The previous code is needed for settings change and validation
		if ( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) == 'default' ) {
			return;
		}

		// If the mod_rewrite is not set in Apache, return
		if ( HMWP_Classes_Tools::isApache() && ! HMWP_Classes_Tools::isModeRewrite() ) {
			return;
		}

		// If safe parameter is set, clear the banned IPs and let the default paths
		if ( HMWP_Classes_Tools::calledSafeUrl( ) ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->clearBlockedIPs();
			HMWP_Classes_Tools::saveOptions( 'banlist_ip', json_encode( array() ) );

			add_filter( 'site_url', array( $this->model, 'site_url' ), PHP_INT_MAX, 2 );
			add_filter( 'hmwp_process_init', '__return_false' );

			return;
		}

		// Prevent slow websites due to misconfiguration in the config file
		if ( count( (array) HMWP_Classes_Tools::getOption( 'file_mappings' ) ) > 0 ) {

			if ( HMWP_Classes_Tools::getOption( 'prevent_slow_loading' ) ) {
				return;
			}

			add_filter( 'hmwp_process_hide_urls', '__return_false' );
		}

		// Check the whitelist IPs & Paths for accessing the hide paths
		/** @var HMWP_Controllers_Firewall $firewall */
		$firewall = HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Firewall' );
		$firewall->checkWhitelistIPs();
		$firewall->checkWhitelistPaths();

		// Load the compatibility class when the plugin loads
		// Check boot compatibility for some plugins and functionalities
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility' )->checkCompatibility();


		// Don't let to rename and hide the current paths if logout is required
		if ( HMWP_Classes_Tools::getOption( 'error' ) || HMWP_Classes_Tools::getOption( 'logout' ) ) {
			return;
		}

		// Check if the custom paths ar set to be processed
		if ( ! apply_filters( 'hmwp_process_init', true ) ) {
			return;
		}

		// Rename the author if set so
		add_filter( 'author_rewrite_rules', array( $this->model, 'author_url' ), PHP_INT_MAX, 1 );

		// Filters
		add_filter( 'query_vars', array( $this->model, 'addParams' ), 1, 1 );
		add_filter( 'login_redirect', array( $this->model, 'sanitize_login_redirect' ), 9, 3 );
		add_filter( 'wp_redirect', array( $this->model, 'sanitize_redirect' ), PHP_INT_MAX, 2 );
		add_filter( 'x_redirect_by', '__return_false', PHP_INT_MAX, 1 );

		// Plugin redirect based on current user role
		if ( HMWP_Classes_Tools::getOption( 'hmwp_do_redirects' ) ) {
			add_action( 'wp_login', array( $this->model, 'wp_login' ), PHP_INT_MAX, 2 );
			add_action( 'set_current_user', array( 'HMWP_Classes_Tools', 'setCurrentUserRole' ), PHP_INT_MAX );
			add_filter( 'hmwp_url_login_redirect', array( 'HMWP_Classes_Tools', 'getCustomLoginURL' ), 10, 1 );
			add_filter( 'hmwp_url_logout_redirect', array( 'HMWP_Classes_Tools', 'getCustomLogoutURL' ), 10, 1 );
			add_filter( 'woocommerce_login_redirect', array( 'HMWP_Classes_Tools', 'getCustomLoginURL' ), 10, 1 );
		}

		// Custom hook for WPEngine
		if ( HMWP_Classes_Tools::isWpengine() ) {
			add_filter( 'wp_redirect', array( $this->model, 'loopCheck' ), PHP_INT_MAX, 1 );
		}

		// Actions
		add_action( 'login_init', array( $this->model, 'login_init' ), PHP_INT_MAX );
		add_action( 'login_head', array( $this->model, 'login_head' ), PHP_INT_MAX );
		add_action( 'wp_logout', array( $this->model, 'wp_logout' ), PHP_INT_MAX );
		add_action( 'check_admin_referer', array( $this->model, 'check_admin_referer' ), PHP_INT_MAX, 2 );
		// Change the admin url and login url
		if( empty( (array) HMWP_Classes_Tools::getOption( 'file_mappings' ) ) ) {
			add_filter( 'admin_url', array( $this->model, 'admin_url' ), PHP_INT_MAX, 3 );
		}
		add_filter( 'lostpassword_url', array( $this->model, 'lostpassword_url' ), PHP_INT_MAX, 1 );
		add_filter( 'login_title', array( $this->model, 'login_title' ), PHP_INT_MAX, 1 );
		add_filter( 'register', array( $this->model, 'register_url' ), PHP_INT_MAX, 1 );
		add_filter( 'login_url', array( $this->model, 'login_url' ), PHP_INT_MAX, 1 );
		add_filter( 'logout_url', array( $this->model, 'logout_url' ), PHP_INT_MAX, 2 );
		add_filter( 'network_admin_url', array( $this->model, 'network_admin_url' ), PHP_INT_MAX, 3 );
		add_filter( 'site_url', array( $this->model, 'site_url' ), PHP_INT_MAX, 2 );
		add_filter( 'network_site_url', array( $this->model, 'site_url' ), PHP_INT_MAX, 3 );
		add_filter( 'plugins_url', array( $this->model, 'plugin_url' ), PHP_INT_MAX, 3 );

		add_filter( 'wp_php_error_message', array( $this->model, 'replace_error_message' ), PHP_INT_MAX, 2 );
		// Change the rest api if needed
		add_filter( 'rest_url_prefix', array( $this->model, 'replace_rest_api' ), 1 );

		// Check and set the cookied for the modified urls
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cookies' );

		// Start the buffer sooner if one of these conditions
		// If is ajax call... start the buffer right away
		// Is always change the paths
		if ( HMWP_Classes_Tools::isAjax() || HMW_ALWAYS_CHANGE_PATHS ) {

			// Starts the buffer
			$this->model->startBuffer();

		}

		// If not dashboard
		if ( ! is_admin() && ! is_network_admin() ) {

			// Check if buffer priority
			if ( apply_filters( 'hmwp_priority_buffer', HMWP_Classes_Tools::getOption( 'hmwp_priorityload' ) ) ) {
				// Starts the buffer
				$this->model->startBuffer();
			}

			// Hook the rss & feed
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_in_feed' ) ) {
				add_action( 'the_excerpt_rss', array( $this->model, 'find_replace' ) );
				add_action( 'the_content_feed', array( $this->model, 'find_replace' ) );
				add_action( 'rss2_head', array( $this->model, 'find_replace' ) );
				add_action( 'commentsrss2_head', array( $this->model, 'find_replace' ) );
				add_action( 'the_permalink_rss', array( $this->model, 'find_replace_url' ) );
				add_action( 'comments_link_feed', array( $this->model, 'find_replace_url' ) );
				add_action( 'get_site_icon_url', array( $this->model, 'find_replace_url' ) );
			}

			// Hide WP version
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_version' ) ) {
				add_filter( 'get_the_generator_atom', '__return_empty_string', 99, 2 );
				add_filter( 'get_the_generator_comment', '__return_empty_string', 99, 2 );
				add_filter( 'get_the_generator_export', '__return_empty_string', 99, 2 );
				add_filter( 'get_the_generator_html', '__return_empty_string', 99, 2 );
				add_filter( 'get_the_generator_rdf', '__return_empty_string', 99, 2 );
				add_filter( 'get_the_generator_rss2', '__return_empty_string', 99, 2 );
				add_filter( 'get_the_generator_xhtml', '__return_empty_string', 99, 2 );
			}

			// Check the buffer on shutdown
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_in_sitemap' ) && isset( $_SERVER['REQUEST_URI'] ) ) {

				// remove sitemap providers
				if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_author_in_sitemap' ) ) {
					add_filter('wp_sitemaps_add_provider', function($provider, $name) {
						if ($name === 'users') {
							return false;
						}
						return $provider;
					}, 99, 2);
				}

				// Check the buffer on shutdown
				add_action( 'shutdown', array( $this->model, 'findReplaceXML' ), 0 ); //priority 0 is important
			}

			// Hide authors and users identification from website
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_authors' ) ) {

				// Remove users from oembed
				add_filter('oembed_response_data', function ($data, $post, $width, $height) {
					unset($data['author_name']);
					unset($data['author_url']);
					return $data;
				}, 99, 4);

				// Remove users from Rest API call
				add_filter('rest_endpoints', array( $this->model, 'hideRestUsers' ), 99);

				// Remove user list from sitemaps
				add_filter( 'wp_sitemaps_users_pre_url_list', '__return_false', 99, 0 );
			}

			// Robots.txt compatibility with other plugins
			if ( HMWP_Classes_Tools::getOption( 'hmwp_robots' ) && isset( $_SERVER['REQUEST_URI'] ) ) {
				if ( strpos( $_SERVER['REQUEST_URI'], '/robots.txt' ) !== false ) {
					add_action( 'shutdown', array( $this->model, 'replaceRobots' ), 0 ); //priority 0 is important
				}
			}

			// Hook the change paths on init
			add_action( 'init', array( $this, 'hookChangePaths' ) );

			// Load the PluginLoaded Hook to hide URLs and Disable stuff
			add_action( 'init', array( $this, 'hookHideDisable' ) );

		}

		// Load firewall on request for all server types
		add_action( 'plugins_loaded', array( $firewall, 'run' ) );

		// Hide the URLs from admin and login
		// Load the hook on plugins_loaded to prevent any wp redirect
		add_action( 'plugins_loaded', array( $this->model, 'hideUrls' ) );

	}

	/**
	 * Hook method to hide and disable specific options based on provided criteria.
	 *
	 * This method performs various actions to enhance the security and privacy of the WordPress site
	 * by hiding or disabling certain functionalities such as headers, REST API, comments, emojis, etc.
	 * depending on the options set in the system tools. It also handles disabling clicks and key interactions
	 * for visitors and logged-in users based on their roles.
	 *
	 * @return void
	 * @throws Exception If any of the operations within this method encounter an error.
	 */
	public function hookHideDisable() {

		//Check if is valid for moving on
		if ( HMWP_Classes_Tools::doHideDisable() ) {
			//////////////////////////////////Hide Options

			// add the security header if needed
			if ( ! HMWP_Classes_Tools::isApache() && ! HMWP_Classes_Tools::isLitespeed() ) {
				//avoid duplicates
				add_action( 'template_redirect', array( $this->model, 'addSecurityHeader' ), PHP_INT_MAX );
			}

			//remove PHP version, Server info, Server Signature from header.
			add_action( 'template_redirect', array( $this->model, 'hideHeaders' ), PHP_INT_MAX );

			//Hide the WordPress Generator tag
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_generator' ) ) {
				remove_action( 'wp_head', 'wp_generator' );
				add_filter( 'the_generator', '__return_false', PHP_INT_MAX, 1 );
			}


			//Hide the rest_api
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_rest_api' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_rest_api' ) ) {
				$this->model->hideRestApi();
			}

			//Hide Really Simple Discovery
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_rsd' ) ) {
				$this->model->disableRsd();
			}

			//Hide WordPress comments
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_comments' ) ) {
				$this->model->disableComments();
			}

			//Hide Windows Live Write
			if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_manifest' ) ) {
				$this->model->disableManifest();
			}

			//////////////////////////////////Disable Options

			//Disable the Emojiicons tag
			if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_emojicons' ) ) {
				$this->model->disableEmojicons();
			}

			//Disable xml-rpc ony if not Apache server
			//for apache server add the .htaccess rules
			if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_xmlrpc' ) && ! HMWP_Classes_Tools::isApache() ) {
				add_filter( 'xmlrpc_enabled', '__return_false' );
			}

			//Disable the embeds
			if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_embeds' ) ) {
				$this->model->disableEmbeds();
			}

			//Disable the admin bar whe users are hidden in admin
			if ( HMWP_Classes_Tools::getOption( 'hmwp_hide_admin_toolbar' ) ) {
				if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {

					HMWP_Classes_Tools::setCurrentUserRole();
					$role = HMWP_Classes_Tools::getUserRole();

					$selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_hide_admin_toolbar_roles' );

					if ( in_array( $role, $selected_roles ) ) {
						add_filter( 'show_admin_bar', '__return_false' );
					}

				}
			}

			//Disable Database Debug
			if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_debug' ) ) {
				global $wpdb;
				$wpdb->hide_errors();
			}


		}

		//Check if Disable keys and mouse action is on
		if ( HMWP_Classes_Tools::doDisableClick() ) {

			//only disable the click and keys wfor visitors
			if ( ! is_user_logged_in() ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Clicks' );
			} else {

				HMWP_Classes_Tools::setCurrentUserRole();
				$role = HMWP_Classes_Tools::getUserRole();

				if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_click_loggedusers' ) ) {
					$selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_click_roles' );

					if ( ! in_array( $role, $selected_roles ) ) {
						add_filter( 'hmwp_option_hmwp_disable_click', '__return_false' );
					}
				} else {
					add_filter( 'hmwp_option_hmwp_disable_click', '__return_false' );
				}

				if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect_loggedusers' ) ) {
					$selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect_roles' );

					if ( ! in_array( $role, $selected_roles ) ) {
						add_filter( 'hmwp_option_hmwp_disable_inspect', '__return_false' );
					}
				} else {
					add_filter( 'hmwp_option_hmwp_disable_inspect', '__return_false' );
				}

				if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_source_loggedusers' ) ) {
					$selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_source_roles' );

					if ( ! in_array( $role, $selected_roles ) ) {
						add_filter( 'hmwp_option_hmwp_disable_source', '__return_false' );
					}
				} else {
					add_filter( 'hmwp_option_hmwp_disable_source', '__return_false' );
				}

				if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste_loggedusers' ) ) {
					$selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste_roles' );

					if ( ! in_array( $role, $selected_roles ) ) {
						add_filter( 'hmwp_option_hmwp_disable_copy_paste', '__return_false' );
					}
				} else {
					add_filter( 'hmwp_option_hmwp_disable_copy_paste', '__return_false' );
				}

				if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop_loggedusers' ) ) {
					$selected_roles = (array) HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop_roles' );

					if ( ! in_array( $role, $selected_roles ) ) {
						add_filter( 'hmwp_option_hmwp_disable_drag_drop', '__return_false' );
					}
				} else {
					add_filter( 'hmwp_option_hmwp_disable_drag_drop', '__return_false' );
				}

				//check again if the options are active after the filrter are applied
				if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_click' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_source' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop' ) ) {

					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Clicks' );

				}

			}

		}

	}


	/**
	 * Main hook to change paths and handle compatibility with other plugins.
	 *
	 * This method checks for mapping files and initiates actions based on current settings and options.
	 * It ensures proper handling of broken URLs, late loading configurations, and integrations with other plugins.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function hookChangePaths() {

		if ( ! HMWP_Classes_Tools::getValue( 'hmwp_preview' ) ) {
			// If not frontend preview/testing

			if ( (HMWP_Classes_Tools::getOption( 'hmwp_mapping_text_show' ) && HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' )) || count( (array) HMWP_Classes_Tools::getOption( 'file_mappings' ) ) > 0 ) {
				// Load MappingFile Check the Mapping Files
				// Check the mapping file in case of config issues or missing rewrites
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Files' )->maybeShowFile();

			}

			// In case of broken URL, try to load it
			// Priority 1 is working for broken files
			add_action( 'template_redirect', array( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Files' ), 'maybeShowNotFound' ), 1 );

		}

		//Check Compatibilities with other plugins
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Compatibility' )->checkBuildersCompatibility();

		///////////////////////////////////////////////
		/// Check if changing the paths is true
		if ( HMWP_Classes_Tools::doChangePaths() ) {
			$priority = apply_filters( 'hmwp_priority_hook', 1 );

			// If there is late loading, start the buffer on template_redirect PHP_INT_MAX
			if ( apply_filters( 'hmwp_laterload', HMWP_Classes_Tools::getOption( 'hmwp_laterload' ) ) ) {
				// On Late loading, start the buffer on template_redirect
				$priority = apply_filters( 'hmwp_priority_hook', PHP_INT_MAX );
			}

			// Start the buffer on template_redirect
			add_action( 'template_redirect', array( $this->model, 'startBuffer' ), $priority  );

			// For login page
			add_action( 'login_init', array( $this->model, 'startBuffer' ) );

		}
	}

	/**
	 * Initialize hooks for the plugin.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function hookInit() {

		// If the user changes the Permalink to default ... prevent errors
		if ( HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) && HMWP_Classes_Tools::getValue( 'settings-updated' ) ) {
			if ( 'default' <> HMWP_Classes_Tools::getOption( 'hmwp_mode' ) ) {
				$this->model->flushChanges();
			}
		}

		// Show the menu for admins only
		HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Menu' )->hookInit();

	}

}
