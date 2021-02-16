<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Handles the parameters and url
 *
 * @author StarBox
 */
class HMW_Classes_Tools extends HMW_Classes_FrontController {

	/** @var array Saved options in database */
	public static $init = array(), $default = array(), $lite = array(), $ninja = array();
	public static $options = array();
	public static $debug = array();
	public static $is_multisite;
	public static $active_plugins;

	/** @var integer Count the errors in site */
	static $errors_count = 0;
	/** @var string current user role */
	static $current_user_role = 'default';

	public function __construct() {
		//Check the max memory usage
		$maxmemory = self::getMaxMemory();
		if ( $maxmemory && $maxmemory < 60 ) {
			if ( defined( 'WP_MAX_MEMORY_LIMIT' ) && (int) WP_MAX_MEMORY_LIMIT > 60 ) {
				@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
				$maxmemory = self::getMaxMemory();
				if ( $maxmemory && $maxmemory < 60 ) {
					define( 'HMW_DISABLE', true );
					HMW_Classes_Error::setError( sprintf( __( 'Your memory limit is %sM. You need at least %sM to prevent loading errors in frontend. See: %sIncreasing memory allocated to PHP%s', _HMW_PLUGIN_NAME_ ), $maxmemory, 64, '<a href="https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">', '</a>' ) );
				}
			} else {
				define( 'HMW_DISABLE', true );
				HMW_Classes_Error::setError( sprintf( __( 'Your memory limit is %sM. You need at least %sM to prevent loading errors in frontend. See: %sIncreasing memory allocated to PHP%s', _HMW_PLUGIN_NAME_ ), $maxmemory, 64, '<a href="https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">', '</a>' ) );
			}
		}
		//Get the plugin options from database
		self::$options = self::getOptions();

		//Load multilanguage
		add_filter( "init", array( $this, 'loadMultilanguage' ) );

		//add review link in plugin list
		add_filter( "plugin_row_meta", array( $this, 'hookExtraLinks' ), 10, 4 );

		//add setting link in plugin
		add_filter( 'plugin_action_links', array( $this, 'hookActionlink' ), 5, 2 );
	}

	/**
	 * Check the memory and make sure it's enough
	 * @return bool|string
	 */
	public static function getMaxMemory() {
		try {
			$memory_limit = @ini_get( 'memory_limit' );
			if ( (int) $memory_limit > 0 ) {
				if ( preg_match( '/^(\d+)(.)$/', $memory_limit, $matches ) ) {
					if ( $matches[2] == 'G' ) {
						$memory_limit = $matches[1] * 1024 * 1024 * 1024; // nnnM -> nnn MB
					} elseif ( $matches[2] == 'M' ) {
						$memory_limit = $matches[1] * 1024 * 1024; // nnnM -> nnn MB
					} elseif ( $matches[2] == 'K' ) {
						$memory_limit = $matches[1] * 1024; // nnnK -> nnn KB
					}
				}

				if ( (int) $memory_limit > 0 ) {
					return number_format( (int) $memory_limit / 1024 / 1024, 0, '', '' );
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
	 * @return array|mixed|object
	 */
	public static function getOptions( $safe = false ) {
		$keymeta    = HMW_OPTION;
		$homepath   = ltrim( parse_url( site_url(), PHP_URL_PATH ), '/' );
		$pluginurl  = ltrim( parse_url( plugins_url(), PHP_URL_PATH ), '/' );
		$contenturl = ltrim( parse_url( content_url(), PHP_URL_PATH ), '/' );

		if ( $safe ) {
			$keymeta = HMW_OPTION_SAFE;
		}

		self::$init    = array(
			'hmw_ver'                  => 0,
			'api_token'                => false,
			'hmw_token'                => 0,
			'hmw_disable'              => mt_rand( 111111, 999999 ),
			'hmw_disable_name'         => 'hmw_disable',
			'logout'                   => false,
			'error'                    => false,
			'rewrites'                 => 0,
			'test_frontend'            => false,
			'changes'                  => false,
			'admin_notice'             => array(),
			//--
			'hmw_firstload'            => 0,
			'hmw_laterload'            => 0,
			'hmw_fix_relative'         => 1,
			'hmw_shutdown_load'        => 0, //check Hide My WP on shutdown
			//--
			'hmw_remove_third_hooks'   => 0,
			'hmw_send_email'           => 1,
			'hmw_activity_log'         => 1,
			'hmw_activity_log_roles'   => array(),
			'hmw_email_address'        => '',

			//-- Brute Force
			'hmw_bruteforce'           => 0,
			'hmw_bruteforce_log'       => 1,
			'hmw_brute_message'        => __( 'Your IP has been flagged for potential security violations. Please try again in a little while...', _HMW_PLUGIN_NAME_ ),
			'whitelist_ip'             => array(),
			'banlist_ip'               => array(),
			'hmw_hide_classes'         => json_encode( array() ),
			'trusted_ip_header'        => '',
			//
			'brute_use_math'           => 0,
			'brute_max_attempts'       => 5,
			'brute_max_timeout'        => 3600,
			//captcha
			'brute_use_captcha'        => 1,
			'brute_captcha_site_key'   => '',
			'brute_captcha_secret_key' => '',
			'brute_captcha_theme'      => 'light',
			'brute_captcha_language'   => '',
			//
			'hmw_new_plugins'          => array(),
			'hmw_new_themes'           => array(),
			//
			'hmw_in_dashboard'         => 0,
			'hmw_hide_loggedusers'     => 1,
			'hmw_hide_version'         => 1,
			'hmw_hide_header'          => 1,
			'hmw_hide_comments'        => 1,
			'hmw_disable_emojicons'    => 0,
			'hmw_disable_xmlrpc'       => 0,
			'hmw_disable_manifest'     => 1,
			'hmw_disable_embeds'       => 0,
			'hmw_disable_debug'        => 1,
			'hmw_file_cache'           => 0,
			'hmw_security_alert'       => 1,
			'html_cdn_urls'            => array(),

			//
			'hmw_robots'               => 0,
			'hmw_mapping_classes'      => 1,
			'hmw_text_mapping'         => json_encode(
				array(
					'from' => array( 'wp-custom' ),
					'to'   => array( 'custom' ),
				)
			),

			//redirects
			'hmw_url_redirect'         => '.',
			'hmw_url_redirects'        => array( 'default' => array( 'login' => '', 'logout' => '' ) ),
		);
		self::$default = array(
			'hmw_mode'             => 'default',
			'hmw_admin_url'        => 'wp-admin',
			'hmw_login_url'        => 'wp-login.php',
			'hmw_activate_url'     => 'wp-activate.php',
			'hmw_lostpassword_url' => '',
			'hmw_register_url'     => '',
			'hmw_logout_url'       => '',
			'hmw_plugin_url'       => trim( preg_replace( '/' . str_replace( '/', '\/', $homepath ) . '/', '', $pluginurl, 1 ), '/' ),
			'hmw_plugins'          => array(),
			'hmw_themes_url'       => 'themes',
			'hmw_themes'           => array(),
			'hmw_upload_url'       => 'uploads',
			'hmw_admin-ajax_url'   => 'admin-ajax.php',
			'hmw_hideajax_admin'   => 0,
			'hmw_hideajax_paths'   => 0,
			'hmw_tags_url'         => 'tag',
			'hmw_wp-content_url'   => trim( preg_replace( '/' . str_replace( '/', '\/', $homepath ) . '/', '', $contenturl, 1 ), '/' ),
			'hmw_wp-includes_url'  => 'wp-includes',
			'hmw_author_url'       => 'author',
			'hmw_hide_authors'     => 0,
			'hmw_wp-comments-post' => 'wp-comments-post.php',
			'hmw_themes_style'     => 'style.css',
			'hmw_hide_img_classes' => 0,
			'hmw_hide_styleids'    => 0,
			'hmw_wp-json'          => 'wp-json',
			'hmw_disable_rest_api' => 0,
			'hmw_hide_admin'       => 0,
			'hmw_hide_newadmin'    => 0,
			'hmw_hide_login'       => 0,
			'hmw_hide_wplogin'     => 0,
			'hmw_hide_plugins'     => 0,
			'hmw_hide_themes'      => 0,

			//
			'hmw_sqlinjection'     => 0,
			'hmw_hide_commonfiles' => 0,
			'hmw_hide_oldpaths'    => 0,
			'hmw_disable_browsing' => 0,

			'hmw_category_base' => '',
			'hmw_tag_base'      => '',
		);
		self::$lite    = array(
			'hmw_mode'             => 'lite',
			'hmw_login_url'        => 'newlogin',
			'hmw_activate_url'     => 'activate',
			'hmw_lostpassword_url' => 'lostpass',
			'hmw_register_url'     => 'signup',
			'hmw_logout_url'       => '',
			'hmw_admin-ajax_url'   => 'admin-ajax.php',
			'hmw_hideajax_admin'   => 0,
			'hmw_hideajax_paths'   => 1,
			'hmw_plugin_url'       => 'core/modules',
			'hmw_themes_url'       => 'core/assets',
			'hmw_upload_url'       => 'storage',
			'hmw_wp-content_url'   => 'core',
			'hmw_wp-includes_url'  => 'lib',
			'hmw_author_url'       => 'writer',
			'hmw_hide_authors'     => 0,
			'hmw_wp-comments-post' => 'comments',
			'hmw_themes_style'     => 'style.css',
			'hmw_hide_admin'       => 1,
			'hmw_hide_newadmin'    => 0,
			'hmw_hide_login'       => 1,
			'hmw_hide_wplogin'     => 1,
			'hmw_hide_plugins'     => 1,
			'hmw_hide_themes'      => 1,
			'hmw_disable_rest_api' => 0,
			'hmw_hide_styleids'    => 0,
			//
			'hmw_sqlinjection'     => 0,
			'hmw_hide_commonfiles' => 0,
			'hmw_hide_oldpaths'    => 0,
			'hmw_disable_browsing' => 0
		);
		self::$ninja   = array();

		if ( is_multisite() && defined( 'BLOG_ID_CURRENT_SITE' ) ) {
			$options = json_decode( get_blog_option( BLOG_ID_CURRENT_SITE, $keymeta ), true );
		} else {
			$options = json_decode( get_option( $keymeta ), true );
		}

		//make sure it works with WP Client plugin by default
		if ( self::isPluginActive( 'wp-client/wp-client.php' ) ) {
			self::$lite['hmw_wp-content_url'] = 'include';
		}

		//Set default hmw_hide_wplogin
		if ( ! isset( $options['hmw_hide_wplogin'] ) && isset( $options['hmw_hide_login'] ) && $options['hmw_hide_login'] ) {
			$options['hmw_hide_wplogin'] = $options['hmw_hide_login'];
		}

		//upgrade the redirects to the new redirects
		if ( isset( $options['hmw_logout_redirect'] ) && $options['hmw_logout_redirect'] ) {
			$options['hmw_url_redirects']['default']['logout'] = $options['hmw_logout_redirect'];
			unset( $options['hmw_logout_redirect'] );
		}

		if ( is_array( $options ) ) {
			$options = @array_merge( self::$init, self::$default, $options );
		} else {
			$options = @array_merge( self::$init, self::$default );
		}

		$category_base = get_option( 'category_base' );
		$tag_base      = get_option( 'tag_base' );

		if ( is_multisite() && ! is_subdomain_install() && is_main_site() && 0 === strpos( get_option( 'permalink_structure' ), '/blog/' ) ) {
			$category_base = preg_replace( '|^/?blog|', '', $category_base );
			$tag_base      = preg_replace( '|^/?blog|', '', $tag_base );
		}

		$options['hmw_category_base'] = $category_base;
		$options['hmw_tag_base']      = $tag_base;


		return $options;
	}

	/**
	 * Get the option from database
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public static function getOption( $key ) {
		if ( ! isset( self::$options[ $key ] ) ) {
			self::$options = self::getOptions();

			if ( ! isset( self::$options[ $key ] ) ) {
				self::$options[ $key ] = 0;
			}
		}

		return self::$options[ $key ];
	}

	/**
	 * Save the Options in user option table in DB
	 *
	 * @param string $key
	 * @param string $value
	 * @param bool|false $safe
	 *
	 */
	public static function saveOptions( $key = null, $value = '', $safe = false ) {
		$keymeta = HMW_OPTION;

		if ( $safe ) {
			$keymeta = HMW_OPTION_SAFE;
		}

		if ( isset( $key ) ) {
			self::$options[ $key ] = $value;
		}

		if ( is_multisite() && defined( 'BLOG_ID_CURRENT_SITE' ) ) {
			update_blog_option( BLOG_ID_CURRENT_SITE, $keymeta, json_encode( self::$options ) );
		} else {
			update_option( $keymeta, json_encode( self::$options ) );
		}
	}

	/**
	 * Save the options into backup
	 */
	public static function saveOptionsBackup() {
		//Save the working options into backup
		foreach ( self::$options as $key => $value ) {
			HMW_Classes_Tools::saveOptions( $key, $value, true );
		}
	}

	/**
	 * Adds extra links to plugin  page
	 *
	 * @param $meta
	 * @param $file
	 * @param $data
	 * @param $status
	 *
	 * @return array
	 */
	public function hookExtraLinks( $meta, $file, $data = null, $status = null ) {
		if ( $file == _HMW_PLUGIN_NAME_ . '/index.php' ) {
			echo '<style>
                .ml-stars{display:inline-block;color:#ffb900;position:relative;top:3px}
                .ml-stars svg{fill:#ffb900}
                .ml-stars svg:hover{fill:#ffb900}
                .ml-stars svg:hover ~ svg{fill:none}
            </style>';

			$meta[] = "<a href='https://hidemywpghost.com/knowledge-base/' target='_blank'>" . __( 'Documentation', _HMW_PLUGIN_NAME_ ) . "</a>";
			$meta[] = "<a href='https://wordpress.org/support/plugin/hide-my-wp/reviews/#new-post' target='_blank' title='" . __( 'Leave a review', _HMW_PLUGIN_NAME_ ) . "'><i class='ml-stars'><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg></i></a>";
		}

		return $meta;
	}


	/**
	 * Add a link to settings in the plugin list
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return array
	 */
	public function hookActionlink( $links, $file ) {
		if ( $file == _HMW_PLUGIN_NAME_ . '/index.php' ) {
			$link = '<a href="https://hidemywpghost.com/wordpress_update" title="Hide My WP Ghost" target="_blank" style="color:#11967A; font-weight: bold">' . __( 'Upgrade to Premium', _HMW_PLUGIN_NAME_ ) . '</a>';
			$link .= ' | ';
			$link .= '<a href="' . self::getSettingsUrl() . '" title="Hide My Wp Settings">' . __( 'Settings', _HMW_PLUGIN_NAME_ ) . '</a>';
			array_unshift( $links, $link );
		}

		return $links;
	}

	/**
	 * Load the multilanguage support from .mo
	 */
	public static function loadMultilanguage() {
		load_plugin_textdomain( _HMW_PLUGIN_NAME_, false, _HMW_PLUGIN_NAME_ . '/languages/' );
	}

	/**
	 * Check if it's Ajax call
	 * @return bool
	 */
	public static function isAjax() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		return false;
	}

	/**
	 * Change the paths in admin and for logged users
	 * @return bool
	 */
	public static function doChangesAdmin() {
		if ( function_exists( 'is_user_logged_in' ) && function_exists( 'current_user_can' ) ) {
			if ( ! is_admin() && ! is_network_admin() ) {
				if ( HMW_Classes_Tools::getOption( 'hmw_hide_loggedusers' ) || ! is_user_logged_in() ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the plugin settings URL
	 *
	 * @param string $page
	 * @param string $relative
	 *
	 * @return string
	 */
	public static function getSettingsUrl( $page = 'hmw_settings', $relative = false ) {
		if ( $relative ) {
			return 'admin.php?page=' . $page;
		} else {
			if ( ! is_multisite() ) {
				return admin_url( 'admin.php?page=' . $page );
			} else {
				return network_admin_url( 'admin.php?page=' . $page );
			}
		}
	}

	/**
	 * Set the header type
	 *
	 * @param string $type
	 */
	public static function setHeader( $type ) {
		switch ( $type ) {
			case 'json':
				header( 'Content-Type: application/json' );
				break;
			case 'text':
				header( "Content-type: text/plain" );
				break;
		}
	}

	/**
	 * Get a value from $_POST / $_GET
	 * if unavailable, take a default value
	 *
	 * @param string $key Value key
	 * @param boolean $keep_newlines Keep the new lines in variable in case of texareas
	 * @param mixed $defaultValue (optional)
	 *
	 * @return mixed Value
	 */
	public static function getValue( $key = null, $defaultValue = false, $keep_newlines = false ) {
		if ( ! isset( $key ) || $key == '' ) {
			return false;
		}

		$ret = ( isset( $_POST[ $key ] ) ? $_POST[ $key ] : ( isset( $_GET[ $key ] ) ? $_GET[ $key ] : $defaultValue ) );

		if ( is_string( $ret ) === true ) {
			if ( $keep_newlines === false ) {
				if ( in_array( $key, array( 'hmw_email_address', 'hmw_email' ) ) ) { //validate email address
					$ret = preg_replace( '/[^A-Za-z0-9-_\.\#\/\*\@]/', '', $ret );
				} elseif ( in_array( $key, array( 'hmw_disable_name' ) ) ) { //validate url parameter
					$ret = preg_replace( '/[^A-Za-z0-9-_]/', '', $ret );
				} else {
					$ret = preg_replace( '/[^A-Za-z0-9-_\/\.]/', '', $ret ); //validate fields
				}
				$ret = sanitize_text_field( $ret );
			} else {
				$ret = preg_replace( '/[^A-Za-z0-9-_.\#\n\r\s\/\* ]\@/', '', $ret );
				if ( function_exists( 'sanitize_textarea_field' ) ) {
					$ret = sanitize_textarea_field( $ret );
				}
			}
		}

		return wp_unslash( $ret );
	}

	/**
	 * Check if the parameter is set
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public static function getIsset( $key = null ) {
		if ( ! isset( $key ) || $key == '' ) {
			return false;
		}

		return isset( $_POST[ $key ] ) ? true : ( isset( $_GET[ $key ] ) ? true : false );
	}

	/**
	 * Show the notices to WP
	 *
	 * @param $message
	 * @param string $type
	 *
	 * @return string
	 */
	public static function showNotices( $message, $type = '' ) {
		if ( file_exists( _HMW_THEME_DIR_ . 'Notices.php' ) ) {
			ob_start();
			include( _HMW_THEME_DIR_ . 'Notices.php' );
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
	public static function hmw_remote_get( $url, $params = array(), $options = array() ) {
		$options['method'] = 'GET';

		$parameters = '';
		if ( ! empty( $params ) ) {
			foreach ( $params as $key => $value ) {
				if ( $key <> '' ) {
					$parameters .= ( $parameters == "" ? "" : "&" ) . $key . "=" . $value;
				}
			}

			if ( $parameters <> '' ) {
				$url .= ( ( strpos( $url, "?" ) === false ) ? "?" : "&" ) . $parameters;
			}
		}
		//echo $url; exit();
		if ( ! $response = self::hmw_wpcall( $url, $params, $options ) ) {
			return false;
		}

		return $response;
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
	public static function hmw_remote_post( $url, $params = array(), $options = array() ) {
		$options['method'] = 'POST';
		if ( ! $response = self::hmw_wpcall( $url, $params, $options ) ) {
			return false;
		}

		return $response;
	}

	/**
	 * Use the WP remote call
	 *
	 * @param string $url
	 * @param array $params
	 * @param array $options
	 *
	 * @return string
	 */
	private static function hmw_wpcall( $url, $params, $options ) {
		$options['timeout']     = ( isset( $options['timeout'] ) ) ? $options['timeout'] : 30;
		$options['sslverify']   = false;
		$options['httpversion'] = '1.0';

		if ( $options['method'] == 'POST' ) {
			$options['body'] = $params;
			unset( $options['method'] );
			$response = wp_remote_post( $url, $options );
		} else {
			unset( $options['method'] );
			$response = wp_remote_get( $url, $options );
		}
		if ( is_wp_error( $response ) ) {
			HMW_Debug::dump( $response );

			return false;
		}

		$response = self::cleanResponce( wp_remote_retrieve_body( $response ) ); //clear and get the body
		HMW_Debug::dump( 'hmw_wpcall', $url, $options, $response ); //output debug

		return $response;
	}

	/**
	 * Get the Json from responce if any
	 *
	 * @param string $response
	 *
	 * @return string
	 */
	private static function cleanResponce( $response ) {
		$response = trim( $response, '()' );

		return $response;
	}

	/**
	 * Returns true for all server types as the new rules are loaded outside the WordPress rules
	 *
	 * @return boolean
	 */
	public static function isPermalinkStructure() {
		return true;
	}


	/**
	 * Check if HTML Headers to prevent chenging the code for other file extension
	 *
	 * @param array $types
	 *
	 * @return bool
	 */
	public static function isContentHeader( $types = array( 'text/html', 'text/xml' ) ) {
		$headers = headers_list();
		foreach ( $headers as $index => $value ) {
			if ( strpos( $value, ':' ) !== false ) {
				$exploded = @explode( ': ', $value );
				if ( count( $exploded ) > 1 ) {
					$headers[ $exploded[0] ] = $exploded[1];
				}
			}
		}


		if ( ! empty( $types ) && isset( $headers['Content-Type'] ) ) {
			foreach ( $types as $type ) {
				if ( strpos( $headers['Content-Type'], $type ) !== false ) {
					return true;
				}
			}

		} else {
			return false;
		}

		return false;
	}

	/**
	 * Returns true if server is Apache
	 *
	 * @return boolean
	 */
	public static function isApache() {
		global $is_apache;

		//If custom defined
		if ( defined( 'HMW_SERVER_TYPE' ) && strtolower( HMW_SERVER_TYPE ) == 'apache' ) {
			return true;
		}

		if ( self::isFlywheel() ) { //force Nginx on Flywheel server
			return false;
		}

		return $is_apache;
	}

	/**
	 * Check if mode rewrite is on
	 * @return bool
	 */
	public static function isModeRewrite() {
		if ( function_exists( 'apache_get_modules' ) ) {
			$modules = apache_get_modules();
			if ( ! empty( $modules ) ) {
				return in_array( 'mod_rewrite', $modules );
			}
		}

		return true;
	}

	/**
	 * Check whether server is LiteSpeed
	 *
	 * @return bool
	 */
	public static function isLitespeed() {
		$litespeed = false;

		//If custom defined
		if ( defined( 'HMW_SERVER_TYPE' ) && strtolower( HMW_SERVER_TYPE ) == 'litespeed' ) {
			return true;
		}

		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) && stristr( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' ) !== false ) {
			$litespeed = true;
		} elseif ( isset( $_SERVER['SERVER_NAME'] ) && stristr( $_SERVER['SERVER_NAME'], 'LiteSpeed' ) !== false ) {
			$litespeed = true;
		} elseif ( isset( $_SERVER['X-Litespeed-Cache-Control'] ) ) {
			$litespeed = true;
		}

		if ( self::isFlywheel() ) {
			return false;
		}

		return $litespeed;
	}

	/**
	 * Check whether server is Lighthttp
	 *
	 * @return bool
	 */
	public static function isLighthttp() {
		return ( isset( $_SERVER['SERVER_SOFTWARE'] ) && stristr( $_SERVER['SERVER_SOFTWARE'], 'lighttpd' ) !== false );
	}

	/**
	 * Check if multisites with path
	 *
	 * @return bool
	 */
	public static function isMultisites() {
		if ( ! isset( self::$is_multisite ) ) {
			self::$is_multisite = ( is_multisite() && ( ( defined( 'SUBDOMAIN_INSTALL' ) && ! SUBDOMAIN_INSTALL ) || ( defined( 'VHOST' ) && VHOST == 'no' ) ) );
		}

		return self::$is_multisite;
	}


	/**
	 * Returns true if server is nginx
	 *
	 * @return boolean
	 */
	public static function isNginx() {
		global $is_nginx;

		//If custom defined
		if ( defined( 'HMW_SERVER_TYPE' ) && strtolower( HMW_SERVER_TYPE ) == 'nginx' ) {
			return true;
		}

		if ( self::isFlywheel() ) {
			return true;
		}

		return ( $is_nginx || ( isset( $_SERVER['SERVER_SOFTWARE'] ) && stristr( $_SERVER['SERVER_SOFTWARE'], 'nginx' ) !== false ) );
	}

	/**
	 * Returns true if server is Wpengine
	 *
	 * @return boolean
	 */
	public static function isWpengine() {

		//If custom defined
		if ( defined( 'HMW_SERVER_TYPE' ) && strtolower( HMW_SERVER_TYPE ) == 'wpengine' ) {
			return true;
		}

		return ( isset( $_SERVER['WPENGINE_PHPSESSIONS'] ) );
	}

	/**
	 * Returns true if server is Inmotion
	 *
	 * @return boolean
	 */
	public static function isInmotion() {

		//If custom defined
		if ( defined( 'HMW_SERVER_TYPE' ) && strtolower( HMW_SERVER_TYPE ) == 'inmotion' ) {
			return true;
		}

		return ( isset( $_SERVER['SERVER_ADDR'] ) && strpos( @gethostbyaddr( $_SERVER['SERVER_ADDR'] ), 'inmotionhosting.com' ) !== false );
	}

	/**
	 * Returns true if server is Godaddy
	 *
	 * @return boolean
	 */
	public static function isGodaddy() {

		//If custom defined
		if ( defined( 'HMW_SERVER_TYPE' ) && strtolower( HMW_SERVER_TYPE ) == 'godaddy' ) {
			return true;
		}

		return ( file_exists( ABSPATH . 'gd-config.php' ) );
	}

	/**
	 * Returns true if server is Wpengine
	 *
	 * @return boolean
	 */
	public static function isFlywheel() {

		//If custom defined
		if ( defined( 'HMW_SERVER_TYPE' ) && strtolower( HMW_SERVER_TYPE ) == 'flywheel' ) {
			return true;
		}

		return ( isset( $_SERVER['SERVER_SOFTWARE'] ) && stristr( $_SERVER['SERVER_SOFTWARE'], 'Flywheel' ) !== false );
	}


	/**
	 * Returns true if server is IIS
	 *
	 * @return boolean
	 */
	public static function isIIS() {
		global $is_IIS, $is_iis7;

		//If custom defined
		if ( defined( 'HMW_SERVER_TYPE' ) && strtolower( HMW_SERVER_TYPE ) == 'iis' ) {
			return true;
		}

		return ( $is_iis7 || $is_IIS || ( isset( $_SERVER['SERVER_SOFTWARE'] ) && stristr( $_SERVER['SERVER_SOFTWARE'], 'microsoft-iis' ) !== false ) );
	}

	/**
	 * Returns true if windows
	 * @return bool
	 */
	public static function isWindows() {
		return ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' );
	}

	/**
	 * Check if IIS has rewrite 2 structure enabled
	 * @return bool
	 */
	public static function isPHPPermalink() {
		if ( get_option( 'permalink_structure' ) ) {
			if ( strpos( get_option( 'permalink_structure' ), 'index.php' ) !== false || strpos( get_option( 'permalink_structure' ), 'index.html' ) !== false || strpos( get_option( 'permalink_structure' ), 'index.htm' ) !== false ) {
				return true;
			}
		}

		return false;
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
	public static function isPluginActive( $plugin ) {
		if ( empty( self::$active_plugins ) ) {
			self::$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				self::$active_plugins = array_merge( array_values( self::$active_plugins ), array_keys( get_site_option( 'active_sitewide_plugins' ) ) );
			}

		}

		return in_array( $plugin, self::$active_plugins, true );
	}

	/**
	 * Check whether the theme is active.
	 *
	 * @param string $theme Theme folder/main file.
	 *
	 * @return boolean
	 */
	public static function isThemeActive( $theme ) {
		if ( function_exists( 'wp_get_theme' ) ) {
			$themes = wp_get_theme();
			if ( isset( $themes->name ) && ( strtolower( $themes->name ) == strtolower( $theme ) || strtolower( $themes->name ) == strtolower( $theme ) . ' child' ) ) {
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
	public static function getAllPlugins() {
		$all_plugins = (array) get_option( 'active_plugins', array() );;

		if ( is_multisite() ) {
			$all_plugins = array_merge( array_values( $all_plugins ), array_keys( get_site_option( 'active_sitewide_plugins' ) ) );
		}

		return $all_plugins;
	}

	/**
	 * Get all the themes names
	 *
	 * @return array
	 */
	public static function getAllThemes() {
		return search_theme_directories();
	}

	/**
	 * Get the absolute filesystem path to the root of the WordPress installation
	 *
	 * @return string Full filesystem path to the root of the WordPress installation
	 */
	public static function getRootPath() {
		if ( defined( '_HMW_CONFIGPATH' ) ) {
			return _HMW_CONFIGPATH;
		} elseif ( self::isFlywheel() && defined( 'WP_CONTENT_DIR' ) && dirname( WP_CONTENT_DIR ) ) {
			return str_replace( '\\', '/', dirname( WP_CONTENT_DIR ) ) . '/';
		} else {
			return ABSPATH;
		}
	}

	/**
	 * Get the config file for WordPress
	 * @return string
	 */
	public static function getConfigFile() {
		if ( file_exists( self::getRootPath() . 'wp-config.php' ) ) {
			return self::getRootPath() . 'wp-config.php';
		}

		if ( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) ) {
			return dirname( ABSPATH ) . '/wp-config.php';
		}

		return false;
	}

	/**
	 * Get Relative path for the current blog in case of WP Multisite
	 *
	 * @param $url
	 *
	 * @return mixed|string
	 */
	public static function getRelativePath( $url ) {
		$url = wp_make_link_relative( $url );

		if ( $url <> '' ) {
			$url = str_replace( wp_make_link_relative( get_bloginfo( 'url' ) ), '', $url );

			if ( HMW_Classes_Tools::isMultisites() && defined( 'PATH_CURRENT_SITE' ) ) {
				$url = str_replace( rtrim( PATH_CURRENT_SITE, '/' ), '', $url );
				$url = trim( $url, '/' );
				$url = $url . '/';
			} else {
				$url = trim( $url, '/' );
			}
		}

		return $url;
	}

	/**
	 * Empty the cache from other cache plugins when save the settings
	 */
	public static function emptyCache() {
		//Empty WordPress rewrites count for 404 error.
		//This happens when the rules are not saved through config file
		HMW_Classes_Tools::saveOptions( 'rewrites', 0 );

		if ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
		}

		if ( function_exists( 'w3tc_minify_flush' ) ) {
			w3tc_minify_flush();
		}
		if ( function_exists( 'w3tc_dbcache_flush' ) ) {
			w3tc_dbcache_flush();
		}
		if ( function_exists( 'w3tc_objectcache_flush' ) ) {
			w3tc_objectcache_flush();
		}

		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}

		if ( function_exists( 'rocket_clean_domain' ) && function_exists( 'rocket_clean_minify' ) && function_exists( 'rocket_clean_cache_busting' ) ) {
			// Remove all cache files
			rocket_clean_domain();
			rocket_clean_minify();
			rocket_clean_cache_busting();
		}

		if ( function_exists( 'opcache_reset' ) ) {
			// Remove all opcache if enabled
			opcache_reset();
		}

		if ( function_exists( 'apc_clear_cache' ) ) {
			// Remove all apc if enabled
			apc_clear_cache();
		}

		if ( class_exists( 'Cache_Enabler_Disk' ) && method_exists( 'Cache_Enabler_Disk', 'clear_cache' ) ) {
			// clear disk cache
			Cache_Enabler_Disk::clear_cache();
		}

		if ( self::isPluginActive( 'hummingbird-performance/wp-hummingbird.php' ) ) {
			do_action( 'wphb_clear_page_cache' );
		}

		if ( class_exists( 'LiteSpeed_Cache' ) ) {
			LiteSpeed_Cache::get_instance()->purge_all();
		}

		if ( class_exists( 'WpeCommon' ) ) {
			if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {
				WpeCommon::purge_memcached();
			}
			if ( method_exists( 'WpeCommon', 'clear_maxcdn_cache' ) ) {
				WpeCommon::clear_maxcdn_cache();
			}
			if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
				WpeCommon::purge_varnish_cache();
			}
		}

		if ( self::isPluginActive( 'sg-cachepress/sg-cachepress.php' ) && class_exists( 'Supercacher' ) ) {
			if ( method_exists( 'Supercacher', 'purge_cache' ) && method_exists( 'Supercacher', 'delete_assets' ) ) {
				Supercacher::purge_cache();
				Supercacher::delete_assets();
			}
		}

		//Clear the fastest cache
		global $wp_fastest_cache;
		if ( isset( $wp_fastest_cache ) && method_exists( $wp_fastest_cache, 'deleteCache' ) ) {
			$wp_fastest_cache->deleteCache();
		}

	}

	/**
	 * Flush the WordPress rewrites
	 */
	public static function flushWPRewrites() {
		if ( HMW_Classes_Tools::isPluginActive( 'woocommerce/woocommerce.php' ) ) {
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		}

		flush_rewrite_rules();
	}

	/**
	 * Called on plugin activation
	 */
	public function hmw_activate() {
		set_transient( 'hmw_activate', true );

		$lastsafeoptions = self::getOptions( true );
		if ( isset( $lastsafeoptions['hmw_mode'] ) && ( $lastsafeoptions['hmw_mode'] == 'ninja' || $lastsafeoptions['hmw_mode'] == 'lite' ) ) {
			set_transient( 'hmw_restore', true );
		}

		self::$options            = @array_merge( self::$init, self::$default );
		self::$options['hmw_ver'] = HMW_VERSION_ID;
		self::saveOptions();

		if ( self::getOption( 'changes' ) ) {
			//Initialize the compatibility with other plugins
			HMW_Classes_ObjController::getClass( 'HMW_Models_Compatibility' )->install();
		}
	}

	/**
	 * Called on plugin deactivation
	 */
	public function hmw_deactivate() {
		$options = self::$default;
		//Prevent duplicates
		foreach ( $options as $key => $value ) {
			//set the default params from tools
			HMW_Classes_Tools::saveOptions( $key, $value );
		}

		//clear the locked ips
		HMW_Classes_ObjController::getClass( 'HMW_Controllers_Brute' )->clearBlockedIPs();

		//remove the custom rules
		HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->writeToFile( '', 'HMWP_RULES' );

		//Flush the changes
		HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->flushChanges();

		//Delete the compatibility with other plugins
		HMW_Classes_ObjController::getClass( 'HMW_Models_Compatibility' )->uninstall();
	}

	/**
	 * Call this function on rewrite update from other plugins
	 *
	 * @param $wp_rules
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function checkRewriteUpdate( $wp_rules ) {
		try {
			if ( ! HMW_Classes_Tools::getOption( 'error' ) && ! HMW_Classes_Tools::getOption( 'logout' ) ) {
				//Build the redirect table
				HMW_Classes_ObjController::getClass( 'HMW_Models_Rewrite' )->clearRedirect()->buildRedirect()->setRewriteRules()->flushRewrites();

			}

		} catch ( Exception $e ) {

		}

		return $wp_rules;
	}

	/**
	 * Check for updates
	 * Called on activation
	 */
	public static function checkUpgrade() {
		self::$options = self::getOptions();

		if ( (int) self::$options['hmw_ver'] == 0 ) {
			$homepath   = ltrim( parse_url( site_url(), PHP_URL_PATH ), '/' );
			$pluginurl  = ltrim( parse_url( plugins_url(), PHP_URL_PATH ), '/' );
			$contenturl = ltrim( parse_url( content_url(), PHP_URL_PATH ), '/' );

			if ( self::$options['hmw_mode'] == 'custom' ) {
				self::$options['hmw_mode'] = 'lite';
			}
			self::$options['hmw_plugin_url']     = trim( str_replace( $homepath, '', $pluginurl ), '/' );
			self::$options['hmw_wp-content_url'] = trim( str_replace( $homepath, '', $contenturl ), '/' );
			self::$options['hmw_themes_url']     = 'themes';
			self::$options['hmw_activate_url']   = 'wp-activate.php';

		}

		self::$options['hmw_ver'] = HMW_VERSION_ID;
		self::saveOptions();

	}

	/**
	 * Check if new themes or plugins are added
	 */
	public function checkWpUpdates() {

		if ( HMW_Classes_Tools::getOption( 'hmw_hide_plugins' ) ) {
			$all_plugins = HMW_Classes_Tools::getAllPlugins();
			$dbplugins   = HMW_Classes_Tools::getOption( 'hmw_plugins' );
			foreach ( $all_plugins as $plugin ) {
				if ( is_plugin_active( $plugin ) && isset( $dbplugins['from'] ) && ! empty( $dbplugins['from'] ) ) {
					if ( ! in_array( plugin_dir_path( $plugin ), $dbplugins['from'] ) ) {
						self::saveOptions( 'changes', true );
					}
				}
			}
		}

		if ( HMW_Classes_Tools::getOption( 'hmw_hide_themes' ) ) {
			$all_themes = HMW_Classes_Tools::getAllThemes();
			$dbthemes   = HMW_Classes_Tools::getOption( 'hmw_themes' );
			foreach ( $all_themes as $theme => $value ) {
				if ( is_dir( $value['theme_root'] ) && isset( $dbthemes['from'] ) && ! empty( $dbthemes['from'] ) ) {
					if ( ! in_array( $theme . '/', $dbthemes['from'] ) ) {
						self::saveOptions( 'changes', true );
					}
				}
			}
		}

		if ( self::getOption( 'changes' ) ) {
			//Initialize the compatibility with other plugins
			HMW_Classes_ObjController::getClass( 'HMW_Models_Compatibility' )->install();
		}


	}

	/**
	 * Call API Server
	 *
	 * @param null $email
	 * @param string $redirect_to
	 *
	 * @return array|bool|mixed|object
	 */
	public static function checkApi( $email = null, $redirect_to = '' ) {
		$check        = array();
		$howtolessons = HMW_Classes_Tools::getValue( 'hmw_howtolessons', 0 );
		$monitor      = HMW_Classes_Tools::getValue( 'hmw_monitor', 0 );
		if ( isset( $email ) && $email <> '' ) {
			$args     = array(
				'email'        => $email,
				'url'          => home_url(),
				'howtolessons' => (int) $howtolessons,
				'monitor'      => (int) $monitor,
				'source'       => _HMW_PLUGIN_NAME_
			);
			$response = self::hmw_remote_get( _HMW_API_SITE_ . '/api/free/token', $args, array( 'timeout' => 10 ) );
		} elseif ( self::getOption( 'hmw_token' ) ) {
			$args     = array(
				'token'        => self::getOption( 'hmw_token' ),
				'url'          => home_url(),
				'howtolessons' => (int) $howtolessons,
				'monitor'      => (int) $monitor,
				'source'       => _HMW_PLUGIN_NAME_
			);
			$response = self::hmw_remote_get( _HMW_API_SITE_ . '/api/free/token', $args, array( 'timeout' => 10 ) );
		} else {
			return $check;
		}
		if ( $response && json_decode( $response ) ) {
			$check = json_decode( $response, true );

			HMW_Classes_Tools::saveOptions( 'hmw_token', ( isset( $check['token'] ) ? $check['token'] : 0 ) );
			HMW_Classes_Tools::saveOptions( 'api_token', ( isset( $check['api_token'] ) ? $check['api_token'] : false ) );
			HMW_Classes_Tools::saveOptions( 'error', isset( $check['error'] ) );

			if ( ! isset( $check['error'] ) ) {
				if ( $redirect_to <> '' ) {
					wp_redirect( $redirect_to );
					exit();
				}
			} elseif ( isset( $check['message'] ) ) {
				HMW_Classes_Error::setError( $check['message'] );
			}
		} else {
			//HMW_Classes_Tools::saveOptions('error', true);
			HMW_Classes_Error::setError( sprintf( __( 'CONNECTION ERROR! Make sure your website can access: %s', _HMW_PLUGIN_NAME_ ), '<a href="' . _HMW_SUPPORT_SITE_ . '" target="_blank">' . _HMW_SUPPORT_SITE_ . '</a>' ) . " <br /> " );
		}

		return $check;
	}

	/**
	 * Send the email is case there are major changes
	 * @return bool
	 */
	public static function sendEmail() {
		$email = self::getOption( 'hmw_email_address' );
		if ( $email == '' ) {
			global $current_user;
			$email = $current_user->user_email;
		}

		$line    = "\n" . "________________________________________" . "\n\n";
		$to      = $email;
		$from    = 'no-reply@wpplugins.tips';
		$subject = get_bloginfo( 'name' ) . ' - ' . __( 'New Login Information', _HMW_PLUGIN_NAME_ );
		$message = "Thank you for using Hide My WP Ghost!" . "\n\n";
		$message .= $line;
		$message .= "SPECIAL OFFER: Get Hide My WP Ghost with just $10/website if you buy a 5 Websites License pack." . "\n";
		$message .= "https://hidemywpghost.com/hide-my-wp-pricing/?coupon=5HIDEMYWP65" . "\n";
		$message .= $line . "\n";
		$message .= "Your new website URLs are:" . "\n";
		$message .= "Admin URL: " . admin_url() . "\n";
		$message .= "Login URL: " . site_url( self::$options['hmw_login_url'] ) . "\n";
		$message .= $line . "\n";
		$message .= "Note: If you can't login to your site, just access this URL: \n";
		$message .= site_url() . "/wp-login.php?" . self::getOption( 'hmw_disable_name' ) . "=" . self::$options['hmw_disable'] . "\n\n\n";

		$message .= "Best regards," . "\n";
		$message .= "WPPlugins Team" . "\n";

		$headers   = array();
		$headers[] = 'From: Hide My WP <' . $from . '>';
		$headers[] = 'Content-type: text/plain';

		add_filter( 'wp_mail_content_type', array( 'HMW_Classes_Tools', 'setContentType' ) );

		if ( @wp_mail( $to, $subject, $message, $headers ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set the content type to text/plain
	 * @return string
	 */
	public static function setContentType() {
		return "text/plain";
	}

	/**
	 * Set the current user role for later use
	 *
	 * @param $user
	 *
	 * @return string
	 */
	public static function setCurrentUserRole( $user = null ) {
		$roles = array();

		if ( ! $user && function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
		}

		if ( isset( $user->roles ) ) {
			$roles = ( array ) $user->roles;
		}

		if ( ! empty( $roles ) ) {
			self::$current_user_role = current( $roles );
		}

		return self::$current_user_role;
	}

	/**
	 * Get the user main Role or default
	 * @return mixed|string
	 */
	public static function getUserRole() {
		return self::$current_user_role;
	}

	/**
	 * Customize the redirect for the logout process
	 *
	 * @param $redirect
	 *
	 * @return mixed
	 */
	public static function getCustomLogoutURL( $redirect ) {
		//Get Logout based on user Role
		$role         = self::getUserRole();
		$urlRedirects = self::getOption( 'hmw_url_redirects' );
		if ( isset( $urlRedirects[ $role ]['logout'] ) && $urlRedirects[ $role ]['logout'] <> '' ) {
			$redirect = $urlRedirects[ $role ]['logout'];
		} elseif ( isset( $urlRedirects['default']['logout'] ) && $urlRedirects['default']['logout'] <> '' ) {
			$redirect = $urlRedirects['default']['logout'];
		}

		return $redirect;
	}

	/**
	 * Customize the redirect for the login process
	 *
	 * @param $redirect
	 * @param $user
	 *
	 * @return mixed
	 */
	public static function getCustomLoginURL( $redirect ) {

		//Get Logout based on user Role
		$role         = self::getUserRole();
		$urlRedirects = self::getOption( 'hmw_url_redirects' );
		if ( isset( $urlRedirects[ $role ]['login'] ) && $urlRedirects[ $role ]['login'] <> '' ) {
			$redirect = $urlRedirects[ $role ]['login'];
		} elseif ( isset( $urlRedirects['default']['login'] ) && $urlRedirects['default']['login'] <> '' ) {
			$redirect = $urlRedirects['default']['login'];
		}

		return $redirect;
	}

	/**
	 * Generate a string
	 *
	 * @param int $length
	 *
	 * @return bool|string
	 */
	public static function generateRandomString( $length = 10 ) {
		return substr( str_shuffle( str_repeat( $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil( $length / strlen( $x ) ) ) ), 1, $length );
	}

	/**
	 * Return false on hooks
	 *
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function returnFalse( $param = null ) {
		return false;
	}

	/**
	 * Return true on hooks
	 *
	 * @param string $param
	 *
	 * @return bool
	 */
	public static function returnTrue( $param = null ) {
		return true;
	}


	/**
	 * make hidemywp the first plugin that loads
	 */
	public static function movePluginFirst() {
		//Make sure the plugin is loaded first
		$plugin         = _HMW_PLUGIN_NAME_ . '/index.php';
		$active_plugins = get_option( 'active_plugins' );

		if ( ! empty( $active_plugins ) ) {

			$this_plugin_key = array_search( $plugin, $active_plugins );

			if ( $this_plugin_key > 0 ) {
				array_splice( $active_plugins, $this_plugin_key, 1 );
				array_unshift( $active_plugins, $plugin );
				update_option( 'active_plugins', $active_plugins );
			}

		}
	}
}