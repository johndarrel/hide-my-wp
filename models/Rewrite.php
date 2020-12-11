<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMW_Models_Rewrite {

	public $_replace = array();
	public $paths;
	//
	protected $_rewrites;
	protected $_replaced;
	protected $_blogurl;
	protected $_pass;
	//
	protected $_findtextmapping = array();
	protected $_replacetextmapping = array();

	public function __construct() {
		$this->_blogurl = str_replace( 'www.', '', parse_url( site_url(), PHP_URL_HOST ) . parse_url( site_url(), PHP_URL_PATH ) );
	}

	/**
	 * Avoid loading the same buffer 2 times in a row
	 * Used if a cache plugin is installed
	 *
	 * @param $hook
	 *
	 * @return bool
	 */
	public function obLoaded( $hook = false ) {
		if ( $hook && function_exists( 'ob_list_handlers' ) ) {
			$buffers = @ob_list_handlers();

			if ( ! empty( $buffers ) ) {
				if ( in_array( $hook, $buffers ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Start the buffer listener
	 */
	public function startBuffer() {
		//If the content is HTML
		$fileModel = HMW_Classes_ObjController::getClass( 'HMW_Models_Files' );

		if ( ! $fileModel->isFile( $fileModel->getCurrentURL() ) ) {
			ob_start( array( $this, 'getBuffer' ) );
		}
	}

	/**
	 * Load on Shutdown and get the buffer for sitemaps
	 * @throws Exception
	 */
	public function shutDownBuffer() {
		//Force to change the URL for xml content types
		if ( HMW_Classes_Tools::isContentHeader( array( 'text/xml' ) ) ) {
			$buffer = $this->find_replace( ob_get_contents() );
			ob_end_clean();
			echo $buffer;
		}
	}

	/**
	 * Get the buffer by hook for compatibility with other plugins
	 * @throws Exception
	 */
	public function getTempBuffer() {
		//Force to change the URL for xml content types
		$buffer = $this->find_replace( ob_get_contents() );

		ob_end_clean();
		echo $buffer;
	}

	/**
	 * Get the output buffer
	 *
	 * @param $buffer
	 *
	 * @return mixed
	 */
	public function getBuffer( $buffer ) {
		if ( HMW_Classes_Tools::isAjax() && HMW_Classes_Tools::getOption( 'hmw_hideajax_paths' ) ) {
			//replace the URLs in Ajax
			if ( function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() ) {
				$buffer = $this->find_replace( $buffer );
			}
		} else {
			$hmw_process_buffer = apply_filters( 'hmw_process_buffer', true );

			if ( HMW_Classes_Tools::isPluginActive( 'wp-rocket/wp-rocket.php' ) ) {

				$rocket_cache_search            = apply_filters( 'rocket_cache_search', false );
				$rocket_override_donotcachepage = apply_filters( 'rocket_override_donotcachepage', false );
				if ( ( function_exists( 'is_404' ) && is_404() )
				     || ( ( function_exists( 'is_search' ) && is_search() ) && ! $rocket_cache_search ) // Don't cache search results.
				     || ( ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) && ! $rocket_override_donotcachepage )
				     || ( defined( 'DONOTROCKETOPTIMIZE' ) && DONOTROCKETOPTIMIZE )
				) {
					$hmw_process_buffer = true;
				}
			}

			if ( $hmw_process_buffer ) {
				//Make sure is permalink set up
				if ( HMW_Classes_Tools::getIsset( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) ) {
					if ( HMW_Classes_Tools::getValue( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) == HMW_Classes_Tools::getOption( 'hmw_disable' ) ) {
						return $buffer;
					}
				}

				if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) <> 'default' ) {//If not in default mode

					//Don't run Hide My WP in these cases
					if ( strlen( $buffer ) < 255 || HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
						return $buffer;
					}

					//Check if other plugins already did the cache
					if ( HMW_Classes_ObjController::getClass( 'HMW_Models_Compatibility' )->alreadyCached() ) {
						return $buffer;
					}

					if ( HMW_Classes_Tools::isContentHeader( array( 'text/html', 'text/xml' ) ) ) {
						//if it's not in admin dashboar or is in admin dashboard but it's not the adminitrator
						//If the user set to change the paths for logged users
						if ( HMW_Classes_Tools::doChangesAdmin() ) {
							$buffer = $this->find_replace( $buffer );
						}
					}
				}

			}
		}

		//Return the buffer to HTML
		return apply_filters( 'hmw_buffer', $buffer );
	}

	/************************************ BUID & FLUSH REWRITES****************************************/
	/**
	 * Prepare redirect build
	 * @return $this
	 */
	public function clearRedirect() {
		HMW_Classes_Tools::$options = HMW_Classes_Tools::getOptions();
		$this->_replace             = array();

		return $this;
	}

	/**
	 * Build the array with find and replace
	 * Decide what goes to htaccess and not
	 * @return $this
	 */
	public function buildRedirect() {
		add_action( 'home_url', array( $this, 'home_url' ), PHP_INT_MAX, 1 );

		if ( ! empty( $this->_replace ) ) {
			return $this;
		}

		if ( HMW_Classes_Tools::getOption( 'hmw_mode' ) <> 'default' ) {
			if ( HMW_Classes_Tools::isMultisites() ) {
				//get all blogs
				global $wpdb;
				$this->paths = array();

				$blogs = $wpdb->get_results( "SELECT path FROM " . $wpdb->blogs . " where blog_id > 1" );
				foreach ( $blogs as $blog ) {
					$this->paths[] = HMW_Classes_Tools::getRelativePath( $blog->path );
				}
			}

			//Redirect the AJAX
			if ( HMW_Classes_Tools::$default['hmw_admin_url'] . '/' . HMW_Classes_Tools::$default['hmw_admin-ajax_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin-ajax_url' ) &&
			     HMW_Classes_Tools::$default['hmw_admin-ajax_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin-ajax_url' ) ) {
				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_admin_url'] . '/' . HMW_Classes_Tools::$default['hmw_admin-ajax_url'];
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_admin-ajax_url' );
				$this->_replace['rewrite'][] = true;

				$this->_replace['from'][]    = HMW_Classes_Tools::getOption( 'hmw_admin_url' ) . '/' . HMW_Classes_Tools::$default['hmw_admin-ajax_url'];
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_admin-ajax_url' );
				$this->_replace['rewrite'][] = false;
			}

			//Redirect the ADMIN
			if ( HMW_Classes_Tools::$default['hmw_admin_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin_url' ) ) {
				$safeoptions = HMW_Classes_Tools::getOptions( true );
				if ( HMW_Classes_Tools::$default['hmw_admin_url'] <> $safeoptions['hmw_admin_url'] ) {
					$this->_replace['from'][]    = "wp-admin" . '/';
					$this->_replace['to'][]      = $safeoptions['hmw_admin_url'] . '/';
					$this->_replace['rewrite'][] = true;
				}
				if ( HMW_Classes_Tools::getOption( 'hmw_admin_url' ) <> $safeoptions['hmw_admin_url'] ) {
					$this->_replace['from'][]    = "wp-admin" . '/';
					$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_admin_url' ) . '/';
					$this->_replace['rewrite'][] = true;
				}
			}


			//Redirect the LOGIN
			if ( HMW_Classes_Tools::$default['hmw_login_url'] <> HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) {
				$this->_replace['from'][]    = "wp-login.php";
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_login_url' );
				$this->_replace['rewrite'][] = true;

				$this->_replace['from'][]    = "wp-login.php";
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_login_url' ) . '/';
				$this->_replace['rewrite'][] = true;
			}

			if ( HMW_Classes_Tools::getOption( 'hmw_lostpassword_url' ) <> '' ) {
				$this->_replace['from'][]    = HMW_Classes_Tools::getOption( 'hmw_login_url' ) . "?action=lostpassword";
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_lostpassword_url' );
				$this->_replace['rewrite'][] = false;

				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_login_url'] . "?action=lostpassword";
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_lostpassword_url' );
				$this->_replace['rewrite'][] = true;
			}

			if ( HMW_Classes_Tools::$default['hmw_activate_url'] <> HMW_Classes_Tools::getOption( 'hmw_activate_url' ) ) {
				if ( HMW_Classes_Tools::getOption( 'hmw_activate_url' ) <> '' ) {
					$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_activate_url'];
					$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_activate_url' );
					$this->_replace['rewrite'][] = true;
				}
			}


			if ( HMW_Classes_Tools::getOption( 'hmw_register_url' ) <> '' ) {
				$this->_replace['from'][]    = HMW_Classes_Tools::getOption( 'hmw_login_url' ) . "?action=register";
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_register_url' );
				$this->_replace['rewrite'][] = false;

				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_login_url'] . "?action=register";
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_register_url' );
				$this->_replace['rewrite'][] = true;
			}

			if ( HMW_Classes_Tools::getOption( 'hmw_logout_url' ) <> '' ) {
				$this->_replace['from'][]    = HMW_Classes_Tools::getOption( 'hmw_login_url' ) . "?action=logout";
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_logout_url' );
				$this->_replace['rewrite'][] = false;

				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_login_url'] . "?action=logout";
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_logout_url' );
				$this->_replace['rewrite'][] = true;
			}

			//Modify plugins urls
			if ( HMW_Classes_Tools::getOption( 'hmw_hide_plugins' ) ) {
				$all_plugins = HMW_Classes_Tools::getOption( 'hmw_plugins' );

				if ( ! empty( $all_plugins['to'] ) ) {
					foreach ( $all_plugins['to'] as $index => $plugin_path ) {
						if ( HMW_Classes_Tools::isMultisites() ) {
							foreach ( $this->paths as $path ) {
								//hmw_Debug::dump($path);
								$this->_replace['from'][]    = $path . HMW_Classes_Tools::$default['hmw_plugin_url'] . '/' . $all_plugins['from'][ $index ];
								$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_plugin_url' ) . '/' . $plugin_path . '/';
								$this->_replace['rewrite'][] = false;
							}
						}

						$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_plugin_url'] . '/' . $all_plugins['from'][ $index ];
						$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_plugin_url' ) . '/' . $plugin_path . '/';
						$this->_replace['rewrite'][] = true;
					}
				}
			}

			//Modify plugins
			if ( HMW_Classes_Tools::$default['hmw_plugin_url'] <> HMW_Classes_Tools::getOption( 'hmw_plugin_url' ) ) {
				if ( HMW_Classes_Tools::isMultisites() ) {
					foreach ( $this->paths as $path ) {
						$this->_replace['from'][]    = $path . HMW_Classes_Tools::$default['hmw_plugin_url'] . '/';
						$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_plugin_url' ) . '/';
						$this->_replace['rewrite'][] = false;
					}
				}
				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_plugin_url'] . '/';
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_plugin_url' ) . '/';
				$this->_replace['rewrite'][] = true;


				//HMW_Debug::dump($this->_replace['from']);

			}

			//Modify themes urls
			if ( HMW_Classes_Tools::getOption( 'hmw_hide_themes' ) ) {
				$all_themes = HMW_Classes_Tools::getOption( 'hmw_themes' );

				if ( ! empty( $all_themes['to'] ) ) {
					foreach ( $all_themes['to'] as $index => $theme_path ) {
						if ( HMW_Classes_Tools::isMultisites() ) {
							foreach ( $this->paths as $path ) {
								$this->_replace['from'][]    = $path . HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' . HMW_Classes_Tools::$default['hmw_themes_url'] . '/' . $all_themes['from'][ $index ];
								$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/' . $theme_path . '/';
								$this->_replace['rewrite'][] = false;

								$this->_replace['from'][]    = $path . HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' . HMW_Classes_Tools::$default['hmw_themes_url'] . '/' . $all_themes['from'][ $index ] . HMW_Classes_Tools::$default['hmw_themes_style'];
								$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/' . $theme_path . '/' . HMW_Classes_Tools::getOption( 'hmw_themes_style' );
								$this->_replace['rewrite'][] = false;
							}
						}


						if ( HMW_Classes_Tools::$default['hmw_themes_style'] <> HMW_Classes_Tools::getOption( 'hmw_themes_style' ) ) {
							$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' . HMW_Classes_Tools::$default['hmw_themes_url'] . '/' . $all_themes['from'][ $index ] . HMW_Classes_Tools::$default['hmw_themes_style'];
							$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/' . $theme_path . '/' . HMW_Classes_Tools::getOption( 'hmw_themes_style' );
							$this->_replace['rewrite'][] = true;

							$this->_replace['from'][]    = HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/' . $theme_path . '/' . HMW_Classes_Tools::$default['hmw_themes_style'];
							$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/' . $theme_path . '/' . HMW_Classes_Tools::getOption( 'hmw_themes_style' );
							$this->_replace['rewrite'][] = false;
						}

						$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' . HMW_Classes_Tools::$default['hmw_themes_url'] . '/' . $all_themes['from'][ $index ];
						$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/' . $theme_path . '/';
						$this->_replace['rewrite'][] = true;
					}

				}
			}

			//Modify theme URL
			if ( HMW_Classes_Tools::$default['hmw_themes_url'] <> HMW_Classes_Tools::getOption( 'hmw_themes_url' ) ) {
				if ( HMW_Classes_Tools::isMultisites() ) {
					foreach ( $this->paths as $path ) {
						$this->_replace['from'][]    = $path . HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' . HMW_Classes_Tools::$default['hmw_themes_url'] . '/';
						$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/';
						$this->_replace['rewrite'][] = false;
					}
				}

				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' . HMW_Classes_Tools::$default['hmw_themes_url'] . '/';
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_themes_url' ) . '/';
				$this->_replace['rewrite'][] = true;

			}

			//Modify uploads
			if ( ! defined( 'UPLOADS' ) ) {
				if ( HMW_Classes_Tools::$default['hmw_upload_url'] <> HMW_Classes_Tools::getOption( 'hmw_upload_url' ) ) {
					if ( HMW_Classes_Tools::isMultisites() ) {
						foreach ( $this->paths as $path ) {
							$this->_replace['from'][]    = $path . HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' . HMW_Classes_Tools::$default['hmw_upload_url'] . '/';
							$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_upload_url' ) . '/';
							$this->_replace['rewrite'][] = false;
						}
					}

					$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' . HMW_Classes_Tools::$default['hmw_upload_url'] . '/';
					$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_upload_url' ) . '/';
					$this->_replace['rewrite'][] = true;

				}
			}

			//Modify wp-content
			if ( HMW_Classes_Tools::$default['hmw_wp-content_url'] <> HMW_Classes_Tools::getOption( 'hmw_wp-content_url' ) ) {
				if ( HMW_Classes_Tools::isMultisites() ) {
					foreach ( $this->paths as $path ) {
						$this->_replace['from'][]    = $path . HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/';
						$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_wp-content_url' ) . '/';
						$this->_replace['rewrite'][] = false;
					}
				}

				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/';
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_wp-content_url' ) . '/';
				$this->_replace['rewrite'][] = true;
			}

			//Modify wp-includes
			if ( HMW_Classes_Tools::$default['hmw_wp-includes_url'] <> HMW_Classes_Tools::getOption( 'hmw_wp-includes_url' ) ) {
				if ( HMW_Classes_Tools::isMultisites() ) {
					foreach ( $this->paths as $path ) {
						$this->_replace['from'][]    = $path . HMW_Classes_Tools::$default['hmw_wp-includes_url'] . '/';
						$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_wp-includes_url' ) . '/';
						$this->_replace['rewrite'][] = false;
					}
				}

				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_wp-includes_url'] . '/';
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_wp-includes_url' ) . '/';
				$this->_replace['rewrite'][] = true;

			}

			//Modify wp-comments-post
			if ( HMW_Classes_Tools::$default['hmw_wp-comments-post'] <> HMW_Classes_Tools::getOption( 'hmw_wp-comments-post' ) ) {
				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_wp-comments-post'];
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_wp-comments-post' ) . '/';
				$this->_replace['rewrite'][] = true;
			}

			//Modify the author link
			if ( HMW_Classes_Tools::$default['hmw_author_url'] <> HMW_Classes_Tools::getOption( 'hmw_author_url' ) ) {
				$this->_replace['from'][]    = HMW_Classes_Tools::$default['hmw_author_url'] . '/';
				$this->_replace['to'][]      = HMW_Classes_Tools::getOption( 'hmw_author_url' ) . '/';
				$this->_replace['rewrite'][] = true;
			}

		}

		return $this;

	}

	/**
	 * Rename all the plugin names with a hash
	 */
	public function hidePluginNames() {
		$dbplugins = array();

		$all_plugins = HMW_Classes_Tools::getAllPlugins();

		foreach ( $all_plugins as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				$dbplugins['to'][]   = substr( md5( $plugin ), 0, 10 );
				$dbplugins['from'][] = str_replace( ' ', '+', plugin_dir_path( $plugin ) );
			}
		}

		HMW_Classes_Tools::saveOptions( 'hmw_plugins', $dbplugins );
	}

	/**
	 * Rename all the themes name with a hash
	 */
	public function hideThemeNames() {
		$dbthemes = array();

		$all_themes = HMW_Classes_Tools::getAllThemes();

		foreach ( $all_themes as $theme => $value ) {
			if ( is_dir( $value['theme_root'] ) ) {
				$dbthemes['to'][]   = substr( md5( $theme ), 0, 10 );
				$dbthemes['from'][] = str_replace( ' ', '+', $theme ) . '/';
			}
		}

		HMW_Classes_Tools::saveOptions( 'hmw_themes', $dbthemes );
	}

	/**
	 * ADMIN_PATH is the new path and set in /config.php
	 * @return $this
	 */
	public function setRewriteRules() {
		$this->_rewrites = array();
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		//Build the redirects
		$this->buildRedirect();

		if ( ! empty( $this->_replace ) ) {
			//form the IIS rewrite
			if ( HMW_Classes_Tools::isIIS() ) {
				foreach ( $this->_replace['to'] as $key => $row ) {
					if ( $this->_replace['rewrite'][ $key ] ) {
						$this->_rewrites[] = array(
							'from' => '([_0-9a-zA-Z-]+/)?' . $this->_replace['to'][ $key ] . ( substr( $this->_replace['to'][ $key ], - 1 ) == '/' ? "(.*)" : "$" ),
							'to'   => $this->_replace['from'][ $key ] . "{R:" . ( substr_count( $this->_replace['to'][ $key ], '(' ) + 2 ) . '}',
						);
					}
				}

				add_filter( 'iis7_url_rewrite_rules', array( $this, 'getIISRules' ) );
			} else {

				if ( HMW_RULES_IN_CONFIG ) { //if the user uses the rules to rewrite the paths
					foreach ( $this->_replace['to'] as $key => $row ) {
						if ( $this->_replace['rewrite'][ $key ] ) {
							$this->_rewrites[] = array(
								'from' => '([_0-9a-zA-Z-]+/)?' . $this->_replace['to'][ $key ] . ( substr( $this->_replace['to'][ $key ], - 1 ) == '/' ? "(.*)" : "$" ),
								'to'   => $this->_replace['from'][ $key ] . ( substr( $this->_replace['to'][ $key ], - 1 ) == '/' ? "$" . ( substr_count( $this->_replace['to'][ $key ], '(' ) + 2 ) : "" ),
							);
						}
					}
				}

				if ( HMW_RULES_IN_WP_RULES ) { //if set to add the HMW rules into WP rules area
					foreach ( $this->_rewrites as $rewrite ) {
						add_rewrite_rule( $rewrite['from'], $rewrite['to'], 'top' );
					}
				}
			}
		}

		//Hook the rewrites rules
		$this->_rewrites = apply_filters( 'hmw_rewrites', $this->_rewrites );

		return $this;
	}

	/******** IIS **********/
	/**
	 * @param string $wrules
	 *
	 * @return string
	 */
	public function getIISRules( $wrules ) {
		$rules = '';
		$path  = parse_url( site_url(), PHP_URL_PATH );
		if ( $path ) {
			$home_root = trailingslashit( $path );
		} else {
			$home_root = '/';
		}

		$rewrites = array();


		if ( ! empty( $this->_replace ) ) {
			foreach ( $this->_replace['to'] as $key => $row ) {
				if ( $this->_replace['rewrite'][ $key ] ) {
					$rewrites[] = array(
						'from' => '([_0-9a-zA-Z-]+/)?' . $this->_replace['to'][ $key ] . ( substr( $this->_replace['to'][ $key ], - 1 ) == '/' ? "(.*)" : "$" ),
						'to'   => $this->_replace['from'][ $key ] . ( substr( $this->_replace['to'][ $key ], - 1 ) == '/' ? "{R:" . ( substr_count( $this->_replace['to'][ $key ], '(' ) + 2 ) . '}' : "" ),
					);
				}
			}
		}

		if ( ! empty( $rewrites ) ) {
			foreach ( $rewrites as $rewrite ) {
				if ( strpos( $rewrite['to'], 'index.php' ) === false ) {
					$rules .= '
                <rule name="HideMyWp: ' . md5( $home_root . $rewrite['from'] ) . '" stopProcessing="true">
                    <match url="^' . $rewrite['from'] . '" ignoreCase="false" />
                    <action type="Rewrite" url="' . $home_root . $rewrite['to'] . '" />
                </rule>';


				}
			}
		}

		return $rules . $wrules;
	}

	/**
	 * @param $config_file
	 *
	 * @return bool
	 */
	public function deleteIISRules( $config_file ) {
		// If configuration file does not exist then rules also do not exist so there is nothing to delete
		if ( ! file_exists( $config_file ) ) {
			return true;
		}

		if ( ! class_exists( 'DOMDocument', false ) ) {
			return false;
		}

		if ( ! HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->isConfigWritable() ) {
			return false;
		}

		try {
			$doc                     = new DOMDocument();
			$doc->preserveWhiteSpace = false;

			if ( $doc->load( $config_file ) === false ) {
				return false;
			}

			$xpath = new DOMXPath( $doc );
			$rules = $xpath->query( '/configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'HideMyWp\')]' );

			if ( $rules->length > 0 ) {
				foreach ( $rules as $item ) {
					$parent = $item->parentNode;
					if ( method_exists( $parent, 'removeChild' ) ) {
						$parent->removeChild( $item );
					}
				}
			}

			if ( ! is_multisite() ) {
				$rules = $xpath->query( '/configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'wordpress\')] | /configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'WordPress\')]' );

				if ( $rules->length > 0 ) {
					foreach ( $rules as $item ) {

						$parent = $item->parentNode;
						if ( method_exists( $parent, 'removeChild' ) ) {
							$parent->removeChild( $item );
						}
					}
				}
			}

			$doc->formatOutput = true;
			saveDomDocument( $doc, $config_file );
		} catch ( Exception $e ) {
		}

		return true;
	}
	/***************************/

	/**
	 * Flush the Rules and write in htaccess or web.config
	 * @return bool
	 */
	public function flushRewrites() {
		$rewritecode = '';
		$config_file = HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->getConfFile();

		$form = '<br />
                    <form method="POST" style="margin: 8px 0;">
                        ' . wp_nonce_field( 'hmw_manualrewrite', 'hmw_nonce', true, false ) . '
                        <input type="hidden" name="action" value="hmw_manualrewrite" />
                        <input type="submit" class="btn rounded-0 btn-success save" value="Okay, I set it up" />
                    </form>
                    ';


		$path = parse_url( site_url(), PHP_URL_PATH );
		if ( $path ) {
			$home_root = trailingslashit( $path );
		} else {
			$home_root = '/';
		}

		//If Windows Server
		if ( HMW_Classes_Tools::isIIS() ) {
			$this->deleteIISRules( $config_file );
			if ( ! iis7_save_url_rewrite_rules() ) {
				$rewritecode .= $this->getIISRules( '' );
				if ( $rewritecode <> '' ) {
					HMW_Classes_Error::setError( sprintf( __( 'IIS detected. You need to update your %s file by adding the following lines after &lt;rules&gt; tag: %s', _HMW_PLUGIN_NAME_ ), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong>' . htmlentities( str_replace( '    ', ' ', $rewritecode ) ) . '</strong></pre>' . $form ) );
					return false; //always show IIS rewrites
				}

			}
		} elseif ( HMW_Classes_Tools::isWpengine() ) {
			$success = true;
			if ( ! empty( $this->_rewrites ) ) {
				$rewritecode .= "<IfModule mod_rewrite.c>" . PHP_EOL. PHP_EOL;
				$rewritecode .= "RewriteEngine On" . PHP_EOL;
				$rewritecode .= "RewriteBase $home_root" . PHP_EOL;
				foreach ( $this->_rewrites as $rewrite ) {
					if ( strpos( $rewrite['to'], 'index.php' ) === false ) {
						$rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $home_root . $rewrite['to'] . " [QSA,L]" . PHP_EOL;
					}
				}
				$rewritecode .= "</IfModule>" . PHP_EOL. PHP_EOL;
			}
			if ( $rewritecode <> '' ) {
				if ( ! HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->writeInHtaccess( $rewritecode, 'HMWP_RULES' ) ) {
					HMW_Classes_Error::setError( sprintf( __( 'Config file is not writable. You need to update your %s file by adding the following lines at the beginning of the file: %s', _HMW_PLUGIN_NAME_ ), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong>' . htmlentities( str_replace( '    ', ' ', $rewritecode ) ) . '</strong></pre>' . $form ) );
					$success = false;
				}
			} else {
				HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->writeInHtaccess( '', 'HMWP_RULES' );
			}

			$rewritecode = '';
			if ( ! empty( $this->_rewrites ) ) {
				foreach ( $this->_rewrites as $rewrite ) {
					if ( PHP_VERSION_ID >= 70400 || ( strpos( $rewrite['to'], 'index.php' ) === false && ( strpos( $rewrite['to'], HMW_Classes_Tools::$default['hmw_wp-content_url'] ) !== false || strpos( $rewrite['to'], HMW_Classes_Tools::$default['hmw_wp-includes_url'] ) !== false ) ) ) {
						if ( strpos( $rewrite['to'], HMW_Classes_Tools::$default['hmw_login_url'] ) === false && strpos( $rewrite['to'], HMW_Classes_Tools::$default['hmw_admin_url'] ) === false ) {
							$rewritecode .= 'Source: <strong>^/' . str_replace( array( '.css', '.js' ), array(
									'\.css',
									'\.js'
								), $rewrite['from'] ) . '</strong> Destination: <strong>' . $home_root . $rewrite['to'] . "</strong> Redirect type: Break;<br />";
						}
					}
				}
			}

			if ( $rewritecode <> '' ) {
				HMW_Classes_Error::setError( sprintf( __( 'WpEngine detected. Add the redirects in the WpEngine Redirect rules panel %s', _HMW_PLUGIN_NAME_ ), '<strong><a href="https://wpengine.com/support/redirect/" target="_blank" style="color: red">' . __( "Learn How To Add the Code", _HMW_PLUGIN_NAME_ ) . '</a></strong> <br /><br /><pre>' . $rewritecode . '</pre>' . $form . '<br />' ) );
				$success = false; //always show WpEngine rewrites
			}

			return $success;
		} elseif ( ( HMW_Classes_Tools::isApache() || HMW_Classes_Tools::isLitespeed() ) ) {
				//Only for Apache servers
				if ( HMW_Classes_Tools::getOption( 'hmw_file_cache' ) && HMW_Classes_Tools::isApache() ) {
					if ( ! HMW_Classes_Tools::isPluginActive( 'wp-rocket/wp-rocket.php' ) && ! HMW_Classes_Tools::isPluginActive( 'wp-fastest-cache/wpFastestCache.php' ) ) {
						$rewritecode .= '<IfModule mod_headers.c>' . PHP_EOL;
						$rewritecode .= 'Header append Vary: Accept-Encoding' . PHP_EOL;
						$rewritecode .= '</IfModule>' . PHP_EOL;

						$rewritecode .= '<IfModule mod_deflate.c>' . PHP_EOL;
						$rewritecode .= 'SetOutputFilter DEFLATE' . PHP_EOL;
						$rewritecode .= '</IfModule>' . PHP_EOL;

						$rewritecode .= '<IfModule mod_filter.c>' . PHP_EOL;
						$rewritecode .= 'AddType x-font/woff .woff' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE image/svg+xml' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE text/plain' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE text/html' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE text/xml' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE text/css' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE text/javascript' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE application/xml' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE application/xhtml+xml' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE application/rss+xml' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE application/javascript' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE application/x-javascript' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE application/x-font-ttf' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE application/vnd.ms-fontobject' . PHP_EOL;
						$rewritecode .= 'AddOutputFilterByType DEFLATE font/opentype font/ttf font/eot font/otf' . PHP_EOL;
						$rewritecode .= '</IfModule>' . PHP_EOL;

						$rewritecode .= '<IfModule mod_expires.c>' . PHP_EOL;
						$rewritecode .= 'ExpiresActive On' . PHP_EOL;
						$rewritecode .= 'ExpiresDefault "access plus 1 month"' . PHP_EOL;
						$rewritecode .= '# Feed' . PHP_EOL;
						$rewritecode .= 'ExpiresByType application/rss+xml "access plus 1 hour"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType application/atom+xml "access plus 1 hour"' . PHP_EOL;
						$rewritecode .= '# CSS, JavaScript' . PHP_EOL;
						$rewritecode .= 'ExpiresByType text/css "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType text/javascript "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType application/javascript "access plus 1 year"' . PHP_EOL . PHP_EOL;
						$rewritecode .= '# Webfonts' . PHP_EOL;
						$rewritecode .= 'ExpiresByType font/ttf "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType font/otf "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType font/woff "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType font/woff2 "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType application/vnd.ms-fontobject "access plus 1 year"' . PHP_EOL . PHP_EOL;
						$rewritecode .= '# Images' . PHP_EOL;
						$rewritecode .= 'ExpiresByType image/jpeg "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType image/gif "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType image/png "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType image/webp "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType image/svg+xml "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType image/x-icon "access plus 1 year"' . PHP_EOL . PHP_EOL;
						$rewritecode .= '# Video' . PHP_EOL;
						$rewritecode .= 'ExpiresByType video/mp4 "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType video/mpeg "access plus 1 year"' . PHP_EOL;
						$rewritecode .= 'ExpiresByType video/webm "access plus 1 year"' . PHP_EOL;
						$rewritecode .= "</IfModule>" . PHP_EOL. PHP_EOL;
					}
				}

			if ( ! empty( $this->_rewrites ) ) {
				$rewritecode .= "<IfModule mod_rewrite.c>" . PHP_EOL;
				$rewritecode .= "RewriteEngine On" . PHP_EOL;
				$rewritecode .= "RewriteBase $home_root" . PHP_EOL;
				foreach ( $this->_rewrites as $rewrite ) {
					if ( strpos( $rewrite['to'], 'index.php' ) === false ) {
						$rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $home_root . $rewrite['to'] . " [QSA,L]" . PHP_EOL;
					}
				}
				$rewritecode .= "</IfModule>" . PHP_EOL. PHP_EOL;
			}

			//disable the xmlrpc in .htaccess only for Apache servers
			//Compatibility with JetPack and other plugins
			if ( HMW_Classes_Tools::getOption( 'hmw_disable_xmlrpc' ) && HMW_Classes_Tools::isApache() ) {
				$rewritecode .= "<Files xmlrpc.php>" . PHP_EOL;
				$rewritecode .= "Order deny,allow" . PHP_EOL;
				$rewritecode .= "Deny from all" . PHP_EOL;
				$rewritecode .= "Allow from 127.0.0.1" . PHP_EOL;
				$rewritecode .= "Allow from *.wordpress.com" . PHP_EOL;
				$rewritecode .= "Allow from 192.0.64.0/18" . PHP_EOL;
				$rewritecode .= "Allow from 185.64.140.0/22" . PHP_EOL;
				$rewritecode .= "Allow from 2a04:fa80::/29" . PHP_EOL;
				$rewritecode .= "Allow from 76.74.255.0/22" . PHP_EOL;
				$rewritecode .= "Allow from 192.0.65.0/22" . PHP_EOL;
				$rewritecode .= "Allow from 192.0.80.0/22" . PHP_EOL;
				$rewritecode .= "Allow from 192.0.96.0/22" . PHP_EOL;
				$rewritecode .= "Allow from 192.0.123.0/22" . PHP_EOL;
				$rewritecode .= "Satisfy All " . PHP_EOL;
				$rewritecode .= "ErrorDocument 404 /" . PHP_EOL;
				$rewritecode .= "</Files>" . PHP_EOL. PHP_EOL;
			}


			if ( $rewritecode <> '' ) {
				if ( !HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->writeInHtaccess( $rewritecode, 'HMWP_RULES' ) ) {
					HMW_Classes_Error::setError( sprintf( __( 'Config file is not writable. You need to update your %s file by adding the following lines at the beginning of the file: %s', _HMW_PLUGIN_NAME_ ), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong>' . htmlentities( str_replace( '    ', ' ', $rewritecode ) ) . '</strong></pre>' . $form ) );
					return false;
				}
			} else {
				HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->writeInHtaccess( '', 'HMWP_RULES' );
			}

		} elseif ( HMW_Classes_Tools::isNginx() ) {
			$cachecode = '';
			if ( ! empty( $this->_rewrites ) ) {
				if ( HMW_Classes_Tools::getOption( 'hmw_file_cache' ) ) {
					$cachecode .= 'location ~* \.(?:ico|css|js|gif|jpe?g|png)$ {' . PHP_EOL;
					$cachecode .= 'expires 365d;' . PHP_EOL;
					$cachecode .= 'add_header Pragma public;' . PHP_EOL;
					$cachecode .= 'add_header Cache-Control "public";' . PHP_EOL;
					$cachecode .= '}' . PHP_EOL . PHP_EOL;
				}

				foreach ( $this->_rewrites as $rewrite ) {
					if ( strpos( $rewrite['to'], 'index.php' ) === false ) {
						$rewritecode .= 'rewrite ^/' . $rewrite['from'] . ' ' . $home_root . $rewrite['to'] . " last;<br />";
					}
				}
				if ( $rewritecode <> '' ) {
					$rewritecode = str_replace( '<br />', PHP_EOL, $rewritecode );
					$rewritecode = $cachecode . 'if (!-e $request_filename) {' . PHP_EOL . $rewritecode . '}';
				}
			}

			if ( $rewritecode <> '' ) {
				if ( ! HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->writeInNginx( $rewritecode, 'HMWP_RULES' ) ) {
					HMW_Classes_Error::setError( sprintf( __( 'Config file is not writable. You have to added it manually at the beginning of the %s file: %s', _HMW_PLUGIN_NAME_ ), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong># BEGIN HMWP_RULES<br />' . htmlentities( str_replace( '    ', ' ', $rewritecode ) ) . '# END HMW_RULES</strong></pre>' ) );
					return false;
				}
			} else {
				HMW_Classes_ObjController::getClass( 'HMW_Models_Rules' )->writeInNginx( '', 'HMWP_RULES' );
			}

		}

		return true;
	}

	/**
	 * Not used yet
	 *
	 * @param $wp_rewrite
	 *
	 * @return mixed
	 */
	public function setRewriteIndexRules( $wp_rewrite ) {
		return $wp_rewrite;
	}

	/**
	 * Flush the changes in htaccess
	 */
	public function flushChanges() {

		if ( ! did_action( 'wp_loaded' ) ) {
			add_action( 'wp_loaded', array( $this, 'flushChanges' ) );
		}

		//Build the redirect table
		$this->clearRedirect()->buildRedirect()->setRewriteRules()->flushRewrites();

		flush_rewrite_rules( true );

		//Hook the flush process for compatibillity usage
		do_action( 'hmw_flushed_rewrites', false );
	}

	/**
	 * Send the email notification
	 */
	public function sendEmail() {
		if ( HMW_Classes_Tools::getOption( 'hmw_send_email' ) ) {
			$options         = HMW_Classes_Tools::getOptions();
			$lastsafeoptions = HMW_Classes_Tools::getOptions( true );

			if ( $lastsafeoptions['hmw_admin_url'] <> $options['hmw_admin_url'] ||
			     $lastsafeoptions['hmw_login_url'] <> $options['hmw_login_url']
			) {
				HMW_Classes_Tools::sendEmail();
			}
		}
	}


	/**
	 * Add the custom param vars for: disable hide my wp and admin tabs
	 *
	 * @param $vars
	 *
	 * @return array
	 */
	public function addParams( $vars ) {
		$vars[] = HMW_Classes_Tools::getOption( 'hmw_disable_name' );
		$vars[] = 'tab';

		return $vars;
	}

	/******************************* RENAME URLS **************************************************/

	public function home_url( $url ) {
		$scheme = ( ( ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ) || ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) || ( function_exists( 'is_ssl' ) && is_ssl() ) ) ? 'https' : 'http' );
		$url    = set_url_scheme( $url, $scheme );

		return $url;
	}

	/**
	 * Get the new admin URL
	 *
	 * @param string $url
	 * @param string $path
	 * @param  integer | null $blog_id
	 *
	 * @return mixed|string
	 */
	public function admin_url( $url, $path = '', $blog_id = null ) {
		$find = $replace = array();

		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
			return $url;
		}

		if ( ! defined( 'ADMIN_COOKIE_PATH' ) ) {
			return $url;
		}


		if ( HMW_Classes_Tools::getIsset( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) ) {
			if ( HMW_Classes_Tools::getValue( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) == HMW_Classes_Tools::getOption( 'hmw_disable' ) ) {
				return add_query_arg( array( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) => HMW_Classes_Tools::getOption( 'hmw_disable' ) ), $url );
			}
		}

		if ( HMW_Classes_Tools::getOption( 'hmw_hide_loggedusers' ) || ( function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() ) ) {
			if ( HMW_Classes_Tools::$default['hmw_admin-ajax_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin-ajax_url' ) ) {
				if ( HMW_Classes_Tools::getOption( 'hmw_hideajax_admin' ) ) {
					$find[] = '/' . HMW_Classes_Tools::$default['hmw_admin_url'] . '/' . HMW_Classes_Tools::$default['hmw_admin-ajax_url'];
				} else {
					$find[] = '/' . HMW_Classes_Tools::$default['hmw_admin-ajax_url'];
				}
				$replace[] = '/' . HMW_Classes_Tools::getOption( 'hmw_admin-ajax_url' );
			}
		}


		if ( HMW_Classes_Tools::$default['hmw_admin_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin_url' ) ) {
			$find[]    = '/' . HMW_Classes_Tools::$default['hmw_admin_url'] . '/';
			$replace[] = '/' . HMW_Classes_Tools::getOption( 'hmw_admin_url' ) . '/';
		}

		$url = str_replace( $find, $replace, $url );

		return $url;
	}

	/**
	 * Change the admin URL for multisites
	 *
	 * @param string $url
	 * @param string $path
	 *
	 * @return mixed|string
	 */
	public function network_admin_url( $url, $path = '' ) {
		$find = $replace = array();
		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
			return $url;
		}

		if ( ! defined( 'ADMIN_COOKIE_PATH' ) ) {
			return $url;
		}


		if ( HMW_Classes_Tools::getOption( 'hmw_admin_url' ) == 'wp-admin' ) {
			return $url;
		}

		if ( HMW_Classes_Tools::getIsset( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) ) {
			if ( HMW_Classes_Tools::getValue( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) == HMW_Classes_Tools::getOption( 'hmw_disable' ) ) {
				return add_query_arg( array( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) => HMW_Classes_Tools::getOption( 'hmw_disable' ) ), $url );
			}
		}


		$from = HMW_Classes_Tools::$default['hmw_admin_url'];
		$to   = HMW_Classes_Tools::getOption( 'hmw_admin_url' );

		$find[]    = network_site_url( $from . '/', $to );
		$replace[] = network_site_url( '/' . HMW_Classes_Tools::getOption( 'hmw_admin_url' ) . '/', $to );

		if ( HMW_Classes_Tools::getOption( 'hmw_hide_loggedusers' ) || ( function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() ) ) {
			if ( HMW_Classes_Tools::$default['hmw_admin-ajax_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin-ajax_url' ) ) {
				if ( HMW_Classes_Tools::getOption( 'hmw_hideajax_admin' ) ) {
					$find[] = '/' . HMW_Classes_Tools::$default['hmw_admin_url'] . '/' . HMW_Classes_Tools::$default['hmw_admin-ajax_url'];
				} else {
					$find[] = '/' . HMW_Classes_Tools::$default['hmw_admin-ajax_url'];
				}
				$replace[] = '/' . HMW_Classes_Tools::getOption( 'hmw_admin-ajax_url' );
			}
		}

		$url = str_replace( $find, $replace, $url );

		return $url;
	}

	/**
	 * Get the new Site URL
	 *
	 * @param string $url
	 * @param string $path
	 *
	 * @return string
	 */
	public function site_url( $url, $path = '' ) {
		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) || $url == '' ) {
			return $url;
		}

		if ( HMW_Classes_Tools::$default['hmw_login_url'] <> HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) {
			//echo $url . '<br />';
			if ( strpos( $url, 'wp-login' ) !== false ) {
				//check if disable and do not redirect to login
				if ( HMW_Classes_Tools::getIsset( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) ) {
					if ( HMW_Classes_Tools::getValue( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) == HMW_Classes_Tools::getOption( 'hmw_disable' ) ) {
						//add the disabled param in order to work without issues
						return add_query_arg( array( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) => HMW_Classes_Tools::getOption( 'hmw_disable' ) ), $url );
					}
				}

				$query = '';
				if ( $path <> '' ) {
					$parsed = @parse_url( $path );
					if ( isset( $parsed['query'] ) && $parsed['query'] <> '' ) {
						$query = '?' . $parsed['query'];
					}
				}

				if ( $query == '?action=lostpassword' && HMW_Classes_Tools::getOption( 'hmw_lostpassword_url' ) <> '' ) {
					$url = site_url( HMW_Classes_Tools::getOption( 'hmw_lostpassword_url' ) );
				} elseif ( $query == '?action=register' && HMW_Classes_Tools::getOption( 'hmw_register_url' ) <> '' ) {
					$url = site_url( HMW_Classes_Tools::getOption( 'hmw_register_url' ) );
				} else {
					$url = site_url() . '/' . HMW_Classes_Tools::getOption( 'hmw_login_url' ) . $query;

					if ( HMW_Classes_Tools::getValue( 'noredirect', false ) ) {
						$url = add_query_arg( array( 'noredirect' => true ), $url );
					}
				}
			}
		}

		if ( HMW_Classes_Tools::$default['hmw_activate_url'] <> HMW_Classes_Tools::getOption( 'hmw_activate_url' ) ) {
			if ( strpos( $url, 'wp-activate.php' ) !== false ) {
				$query = '';
				if ( $path <> '' ) {
					$parsed = @parse_url( $path );
					if ( isset( $parsed['query'] ) && $parsed['query'] <> '' ) {
						$query = '?' . $parsed['query'];
					}
				}
				$url = site_url() . '/' . HMW_Classes_Tools::getOption( 'hmw_activate_url' ) . $query;
			}
		}


		return $url;
	}

	/**
	 * Login Header Hook
	 */
	public function login_head() {
		add_filter( 'login_headerurl', array( $this, 'login_url' ), 99, 1 );
	}

	/**
	 * Get the new Login URL
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public function login_url( $url ) {
		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
			return $url;
		}

		if ( HMW_Classes_Tools::$default['hmw_login_url'] <> HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) {
			//check if disable and do not redirect to login
			if ( HMW_Classes_Tools::getIsset( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) ) {
				if ( HMW_Classes_Tools::getValue( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) == HMW_Classes_Tools::getOption( 'hmw_disable' ) ) {
					//add the disabled param in order to work without issues
					return add_query_arg( array( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) => HMW_Classes_Tools::getOption( 'hmw_disable' ) ), $url );
				}
			}

			$url = site_url( HMW_Classes_Tools::getOption( 'hmw_login_url' ) );

		}


		return $url;
	}

	/**
	 * Hook the Login Init from wp-login.php
	 */
	public function login_init() {
		add_filter( 'wp_safe_redirect_fallback', array( $this, 'loopCheck' ), 99, 1 );
		add_filter( 'wp_redirect', array( $this, 'loopCheck' ), 99, 1 );


		//////////////////////////////// Rewrite the login style
		wp_deregister_script( 'password-strength-meter' );
		wp_deregister_script( 'user-profile' );
		wp_deregister_style( 'forms' );
		wp_deregister_style( 'l10n' );
		wp_deregister_style( 'login' );

		wp_register_style( 'login', _HMW_THEME_URL_ . 'wplogin/css/login.min.css', array(
			'dashicons',
			'buttons',
			'forms',
			'l10n'
		), HMW_VERSION_ID, false );
		wp_register_style( 'forms', _HMW_THEME_URL_ . 'wplogin/css/forms.min.css', null, HMW_VERSION_ID, false );
		wp_register_style( 'l10n', _HMW_THEME_URL_ . 'wplogin/css/l10n.min.css', null, HMW_VERSION_ID, false );
		wp_register_script( 'password-strength-meter', _HMW_THEME_URL_ . 'wplogin/js/password-strength-meter.min.js', array(
			'jquery',
			'zxcvbn-async'
		), HMW_VERSION_ID, true );
		wp_register_script( 'user-profile', _HMW_THEME_URL_ . 'wplogin/js/user-profile.min.js', array(
			'jquery',
			'password-strength-meter',
			'wp-util'
		), HMW_VERSION_ID, true );
		/////////////////////////////////////////////////////////

		//remove clasiera theme loop
		remove_action( "login_init", "classiera_cubiq_login_init" );
		remove_filter( "login_redirect", "buddyboss_redirect_previous_page" );
		remove_filter( "login_redirect", "loginstyle_login_redirect" );

		$isRedirect = HMW_Classes_Tools::getCustomLoginURL( false );
		if ( HMW_Classes_Tools::getValue( 'noredirect', false ) || $isRedirect || HMW_Classes_Tools::getOption( 'hmw_remove_third_hooks' ) ) {
			remove_all_actions( 'login_init' );
			remove_all_actions( 'login_redirect' );
			remove_all_actions( 'bbp_redirect_login' );

			add_filter( 'login_headerurl', array( $this, 'login_url' ) );
			add_filter( 'login_redirect', array( $this, 'sanitize_login_redirect' ), 1, 3 );
		}

		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
			return;
		}

		if ( HMW_Classes_Tools::$default['hmw_login_url'] <> HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) {
			add_filter( 'lostpassword_redirect', array( $this, 'lostpassword_redirect' ), 1 );
			add_filter( 'registration_redirect', array( $this, 'registration_redirect' ), 1 );

			HMW_Classes_ObjController::getClass( 'HMW_Models_Cookies' )->setTestCookie();
		}

		do_action('hmw_login_init');
	}

	/**
	 * Change the password confirm URL with the new URL
	 * @return string
	 */
	public function lostpassword_redirect() {
		return site_url( 'wp-login.php?checkemail=confirm' );
	}

	/**
	 * Change the register confirmation URL with the new URL
	 * @return string
	 */
	public function registration_redirect() {
		return site_url( 'wp-login.php?checkemail=registered' );
	}

	/**
	 * Called from WP hook to change the lost password URL
	 *
	 * @param $url
	 *
	 * @return mixed
	 */
	public function lostpassword_url( $url ) {
		if ( HMW_Classes_Tools::getOption( 'hmw_lostpassword_url' ) <> '' ) {
			$url = str_ireplace( $this->_replace['from'], $this->_replace['to'], $url );
		}

		return $url;
	}

	/**
	 * Called from WP hook to change the register URL
	 *
	 * @param $url
	 *
	 * @return mixed
	 */
	public function register_url( $url ) {
		if ( HMW_Classes_Tools::getOption( 'hmw_register_url' ) <> '' ) {
			$url = str_ireplace( $this->_replace['from'], $this->_replace['to'], $url );
		}

		return $url;
	}

	/**
	 * Get the new Logout URL
	 *
	 * @param string $url
	 * @param string $redirect
	 *
	 * @return string
	 */
	public function logout_url( $url, $redirect = '' ) {
		$args = array();
		if ( $url <> '' ) {
			$parsed = @parse_url( $url );
			if ( $parsed['query'] <> '' ) {
				@parse_str( html_entity_decode( $parsed['query'] ), $args );
			}
		}

		if ( isset( $args['_wpnonce'] ) && HMW_Classes_Tools::getOption( 'hmw_logout_url' ) <> '' ) {
			$url = site_url() . '/' . add_query_arg( array( '_wpnonce' => $args['_wpnonce'] ), HMW_Classes_Tools::getOption( 'hmw_logout_url' ) );
		}

		return $url;
	}

	/**
	 * Get the new Author URL
	 *
	 * @param array $rewrite
	 *
	 * @return mixed
	 */
	public function author_url( $rewrite ) {
		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
			return $rewrite;
		}

		if ( HMW_Classes_Tools::$default['hmw_author_url'] <> HMW_Classes_Tools::getOption( 'hmw_author_url' ) ) {
			foreach ( $rewrite as $from => $to ) {
				$newfrom             = str_replace( HMW_Classes_Tools::$default['hmw_author_url'], HMW_Classes_Tools::getOption( 'hmw_author_url' ), $from );
				$rewrite[ $newfrom ] = $to;
			}
		}

		return $rewrite;
	}

	/******************************** HOOK REDIRECTS *************************************************/

	/**
	 * Hook the logout to flush the changes set in admin
	 */
	public function wp_logout() {
		$_REQUEST['redirect_to'] = apply_filters( 'hmw_url_logout_redirect', site_url() );
	}

	/**
	 * In case of  redirects, correct the redirect links
	 *
	 * @param string $redirect The path or URL to redirect to.
	 * @param string $status The HTTP response status code to use
	 *
	 * @return string
	 * @throws Exception
	 */
	public function sanitize_redirect( $redirect, $status = '' ) {

		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
			return $redirect;
		}

		$parsed = parse_url( $redirect );
		//Check if there is the safe parameter in the url
		if ( isset( $parsed['query'] ) && ! empty( $parsed['query'] ) ) {
			@parse_str( $parsed['query'] );
			if ( isset( $hmw_disable ) ) {
				if ( $hmw_disable == HMW_Classes_Tools::getOption( 'hmw_disable' ) ) {
					$_GET[ HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ] = HMW_Classes_Tools::getOption( 'hmw_disable' );
				}
			}
		}

		if ( HMW_Classes_Tools::$default['hmw_admin_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin_url' ) ) {
			if ( strpos( $redirect, 'wp-admin' ) !== false ) {
				$redirect = $this->admin_url( $redirect );
			}

		}

		return $redirect;

	}

	/**
	 * In case of login redirects, correct the redirect links
	 *
	 * @param string $redirect The path or URL to redirect to.
	 * @param string $path
	 * @param string $user
	 *
	 * @return string
	 * @throws Exception
	 */
	public function sanitize_login_redirect( $redirect, $path = '', $user ) {

		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
			return $redirect;
		}

		$parsed = parse_url( $redirect );
		//Check if there is the safe parameter in the url
		if ( isset( $parsed['query'] ) && ! empty( $parsed['query'] ) ) {
			@parse_str( $parsed['query'] );
			if ( isset( $hmw_disable ) ) {
				if ( $hmw_disable == HMW_Classes_Tools::getOption( 'hmw_disable' ) ) {
					$_GET[ HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ] = HMW_Classes_Tools::getOption( 'hmw_disable' );
				}
			}
		}

		//check if disable and do not redirect to login
		if ( HMW_Classes_Tools::getIsset( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) ) {
			if ( HMW_Classes_Tools::getValue( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) == HMW_Classes_Tools::getOption( 'hmw_disable' ) ) {
				HMW_Classes_Tools::$options = array_merge( HMW_Classes_Tools::$options, HMW_Classes_Tools::$default );
				HMW_Classes_Tools::saveOptions();
				delete_option( HMW_OPTION_SAFE );
				HMW_Classes_ObjController::getClass( 'HMW_Controllers_Brute' )->clearBlockedIPs();
				HMW_Classes_Tools::saveOptions( 'banlist_ip', json_encode( array() ) );

				return site_url( HMW_Classes_Tools::$default['hmw_admin_url'] );
			}
		}

		if ( HMW_Classes_Tools::$default['hmw_login_url'] <> HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) {
			if ( strpos( $redirect, 'wp-login' ) !== false ) {
				$redirect = site_url( HMW_Classes_Tools::getOption( 'hmw_login_url' ) );
			}
		}

		if ( HMW_Classes_Tools::$default['hmw_admin_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin_url' ) ) {
			if ( strpos( $redirect, 'wp-admin' ) !== false ) {
				$redirect = $this->admin_url( $redirect );
			}
		}

		//if user is logged in
		if ( isset( $user ) && isset( $user->ID ) && ! is_wp_error( $user ) ) {
			//Set the current user for custom redirects
			HMW_Classes_Tools::setCurrentUserRole( $user );
		}

		if ( HMW_Classes_Tools::getValue( 'noredirect', false ) || HMW_Classes_Tools::getOption( 'hmw_remove_third_hooks' ) ) {

			//remove other redirect hooks
			remove_all_actions( 'login_redirect' );

			if ( isset( $user ) && isset( $user->ID ) ) {
				if ( ! is_wp_error( $user ) && empty( $_REQUEST['reauth'] ) ) {

					//If admin redirect
					if ( ( empty( $redirect ) || $redirect == 'wp-admin/' || $redirect == admin_url() ) ) {

						// If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
						if ( is_multisite() && ! get_active_blog_for_user( $user->ID ) && ! is_super_admin( $user->ID ) ) {
							$redirect = user_admin_url();
						} elseif ( method_exists( $user, 'has_cap' ) ) {

							if ( is_multisite() && ! $user->has_cap( 'read' ) ) {
								$redirect = get_dashboard_url( $user->ID );
							} elseif ( ! $user->has_cap( 'edit_posts' ) ) {
								$redirect = $user->has_cap( 'read' ) ? admin_url( 'profile.php' ) : site_url();
							}

						}

						//overwrite the login redirect with the custom Hide My WP redirect
						$redirect = apply_filters( 'hmw_url_login_redirect', $redirect);

						wp_safe_redirect( $redirect );
						exit();
					}

					//overwrite the login redirect with the custom Hide My WP redirect
					$redirect = apply_filters( 'hmw_url_login_redirect', $redirect);

					wp_safe_redirect( $redirect );
					exit();
				}
			}
		}

		//overwrite the login redirect with the custom Hide My WP redirect
		$redirect = apply_filters( 'hmw_url_login_redirect', $redirect);

		return $redirect;
	}

	/**
	 * Check if the current URL is the same with the redirect URL
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public function loopCheck( $url ) {
		if ( isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) && $url <> '' ) {
			$current_url  = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$redirect_url = parse_url( $url, PHP_URL_HOST ) . parse_url( $url, PHP_URL_PATH );
			if ( $current_url <> '' && $redirect_url <> '' ) {
				if ( $current_url == $redirect_url ) {
					return add_query_arg( array( 'noredirect' => true ), $url );
				}
			}

		}

		if ( HMW_Classes_Tools::getOption( 'hmw_hide_wplogin' ) || HMW_Classes_Tools::getOption( 'hmw_hide_login' ) ) {
			if ( HMW_Classes_Tools::$default['hmw_login_url'] <> HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) {
				if ( is_user_logged_in() ) {
					$paths = array(
						site_url( 'wp-login.php', 'relative' ),
						site_url( 'wp-login', 'relative' ),
					);
				} else {
					$paths = array(
						home_url( 'wp-login.php', 'relative' ),
						home_url( 'wp-login', 'relative' ),
						site_url( 'wp-login.php', 'relative' ),
						site_url( 'wp-login', 'relative' ),
					);

					if ( HMW_Classes_Tools::getOption( 'hmw_hide_login' ) ) {

						array_push( $paths, home_url( 'login', 'relative' ) );
						array_push( $paths, site_url( 'login', 'relative' ) );

					}

					$paths = array_unique( $paths );
				}

				if ( $this->searchInString( $url, $paths ) ) {
					if ( site_url( HMW_Classes_Tools::getOption( 'hmw_login_url' ), 'relative' ) <> $url ) {
						return add_query_arg( array( 'noredirect' => true ), site_url( HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) );
					}
				}
			}
		}

		return $url;
	}

	/**
	 * Check Hidden pages and return 404 if needed
	 */
	public function hideUrls() {

		if ( HMW_Classes_Tools::getOption( 'error' ) || HMW_Classes_Tools::getOption( 'logout' ) ) {
			return;
		}

		if ( HMW_Classes_Tools::getIsset( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) ) {
			if ( HMW_Classes_Tools::getValue( HMW_Classes_Tools::getOption( 'hmw_disable_name' ) ) == HMW_Classes_Tools::getOption( 'hmw_disable' ) ) {
				return;
			}
		}

		if ( isset( $_SERVER['SERVER_NAME'] ) && isset( $_SERVER["REQUEST_URI"] ) ) {
			$url       = untrailingslashit( strtok( $_SERVER["REQUEST_URI"], '?' ) );
			$http_post = ( 'POST' == $_SERVER['REQUEST_METHOD'] );

			//if user is logged in and is not set to hide the admin urls
			if ( is_user_logged_in() ) {
				//redirect if no final slash is added
				if ( $_SERVER['REQUEST_URI'] == site_url( HMW_Classes_Tools::getOption( 'hmw_admin_url' ), 'relative' ) ) {
					wp_safe_redirect( $url . '/' );
					exit();
				}
			} else if ( HMW_Classes_Tools::getValue( 'action' ) == 'itsec-check-loopback' ) { //IThemes Security check
				$exp    = HMW_Classes_Tools::getValue( 'exp', false );
				$action = 'itsec-check-loopback';
				$hash   = hash_hmac( 'sha1', "{$action}|{$exp}", wp_salt() );

				if ( $hash <> HMW_Classes_Tools::getValue( 'hash', '' ) ) {
					wp_safe_redirect( $url . '/' );
					exit();
				}
			} else {
				//if is set to hide the urls or not logged in
				if ( $url <> '' ) {
					/////////////////////////////////////////////////////
					//Hide Admin URL when changed
					if ( HMW_Classes_Tools::$default['hmw_admin_url'] <> HMW_Classes_Tools::getOption( 'hmw_admin_url' ) ) {
						if ( is_user_logged_in() ) { //if user is logged in
							$paths = array(
								home_url( 'wp-admin', 'relative' ),
								site_url( 'wp-admin', 'relative' )
							);
						} else { //if not logged in
							if ( HMW_Classes_Tools::getOption( 'hmw_hide_newadmin' ) ) {
								if ( strpos( $url . '/', '/' . HMW_Classes_Tools::getOption( 'hmw_admin_url' ) . '/' ) !== false && HMW_Classes_Tools::getOption( 'hmw_hide_admin' ) ) {
									if ( strpos( $url . '/', '/' . HMW_Classes_Tools::getOption( 'hmw_admin-ajax_url' ) . '/' ) === false ) {
										$this->getNotFound( $url );
									}
								}
							} else {
								if ( $_SERVER['REQUEST_URI'] == site_url( HMW_Classes_Tools::getOption( 'hmw_admin_url' ), 'relative' ) ) {
									wp_safe_redirect( $url . '/' );
									exit();
								}
							}

							$paths = array(
								home_url( 'wp-admin', 'relative' ),
								home_url( 'dashboard', 'relative' ),
								home_url( 'admin', 'relative' ),
								site_url( 'wp-admin', 'relative' ),
								site_url( 'dashboard', 'relative' ),
								site_url( 'admin', 'relative' ),
							);
							$paths = array_unique( $paths );
						}

						if ( $this->searchInString( $url, $paths ) ) {
							if ( site_url( HMW_Classes_Tools::getOption( 'hmw_admin_url' ), 'relative' ) <> $url && HMW_Classes_Tools::getOption( 'hmw_hide_admin' ) ) {
								$this->getNotFound( $url );
							}
						}
					} elseif ( ! is_user_logged_in() ) {
						if ( strpos( $url, '/wp-admin' ) !== false && strpos( $url, admin_url( 'admin-ajax.php', 'relative' ) ) === false && HMW_Classes_Tools::getOption( 'hmw_hide_admin' ) ) {
							$this->getNotFound( $url );
						}
					}

					if ( $http_post ) {
						if ( HMW_Classes_Tools::getOption( 'hmw_lostpassword_url' ) <> '' ) {
							if ( strpos( $url, '/' . HMW_Classes_Tools::getOption( 'hmw_lostpassword_url' ) ) !== false ) {
								$_REQUEST['action'] = 'lostpassword';
							}
						}

						if ( HMW_Classes_Tools::getOption( 'hmw_register_url' ) <> '' ) {
							if ( strpos( $url, '/' . HMW_Classes_Tools::getOption( 'hmw_register_url' ) ) !== false ) {
								$_REQUEST['action'] = 'register';
							}
						}
					}

					/////////////////////////////////////////////////////
					//Hide Login URL when changed
					if ( HMW_Classes_Tools::getOption( 'hmw_hide_wplogin' ) || HMW_Classes_Tools::getOption( 'hmw_hide_login' ) ) {
						if ( HMW_Classes_Tools::$default['hmw_login_url'] <> HMW_Classes_Tools::getOption( 'hmw_login_url' ) ) {
							if ( is_user_logged_in() ) {
								$paths = array(
									site_url( 'wp-login.php', 'relative' ),
									site_url( 'wp-login', 'relative' ),
								);
							} else {
								$paths = array(
									home_url( 'wp-login.php', 'relative' ),
									home_url( 'wp-login', 'relative' ),
									site_url( 'wp-login.php', 'relative' ),
									site_url( 'wp-login', 'relative' ),
								);

								if ( HMW_Classes_Tools::getOption( 'hmw_hide_login' ) ) {

									array_push( $paths, home_url( 'login', 'relative' ) );
									array_push( $paths, site_url( 'login', 'relative' ) );

								}

								$paths = array_unique( $paths );

							}

							if ( $this->searchInString( $url, $paths ) ) {

								if ( site_url( HMW_Classes_Tools::getOption( 'hmw_login_url' ), 'relative' ) <> $url ) {
									$this->getNotFound( $url );
								}
							}
						}
					}

					/////////////////////////////////////////////////////
					//Hide the author url when changed
					if ( HMW_Classes_Tools::$default['hmw_author_url'] <> HMW_Classes_Tools::getOption( 'hmw_author_url' ) ) {
						$paths = array(
							home_url( 'author', 'relative' ),
							site_url( 'author', 'relative' ),
						);
						if ( $this->searchInString( $url, $paths ) ) {
							$this->getNotFound( $url );
						}
					}

					/////////////////////////////////////////////////////
					//Hide the common php file in case of other servers
					$paths = array(
						home_url( 'install.php', 'relative' ),
						home_url( 'upgrade.php', 'relative' ),
						home_url( 'wp-signup.php', 'relative' ),
						home_url( 'wp-config.php', 'relative' ),
						home_url( 'bb-config.php', 'relative' ),
						site_url( 'install.php', 'relative' ),
						site_url( 'upgrade.php', 'relative' ),
						site_url( 'wp-signup.php', 'relative' ),
						site_url( 'wp-config.php', 'relative' ),
						site_url( 'bb-config.php', 'relative' ),
					);
					if ( $this->searchInString( $url, $paths ) ) {
						$this->getNotFound( $url );
					}
					/////////////////////////////////////////////////////

				}
			}
		}

	}

	/**
	 * Search part of string in array
	 *
	 * @param $needle
	 * @param $haystack
	 *
	 * @return bool
	 */
	public function searchInString( $needle, $haystack ) {
		foreach ( $haystack as $value ) {
			if ( stripos( $needle . '/', $value . '/' ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return 404 page or redirect
	 *
	 * @param string $url
	 */
	public function getNotFound( $url ) {
		HMW_Debug::dump( $url );
		if ( HMW_Classes_Tools::getOption( 'hmw_url_redirect' ) == '404' ) {
			if ( HMW_Classes_Tools::isThemeActive( 'Pro' ) ) {
				global $wp_query;
				$wp_query->is_404 = true;

				wp_safe_redirect( site_url( '404' ) );
			} else {
				$this->get404Page( true );
			}
		} elseif ( HMW_Classes_Tools::getOption( 'hmw_url_redirect' ) == 'NFError' ) {
			$this->get404Page();
		} elseif ( HMW_Classes_Tools::getOption( 'hmw_url_redirect' ) == '.' ) {
			//redirect to front page
			wp_safe_redirect( site_url() );
		} else {
			//redirect to custom page
			wp_safe_redirect( site_url( HMW_Classes_Tools::getOption( 'hmw_url_redirect' ) ) );
		}

		die();
	}

	/**
	 * Display 404 page to bump bots and bad guys
	 *
	 * @param bool $usetheme If true force displaying basic 404 page
	 */
	function get404Page( $usetheme = false ) {
		global $wp_query;

		if ( function_exists( 'status_header' ) ) {
			status_header( '404' );
		}
		if ( isset( $wp_query ) && is_object( $wp_query ) ) {
			$wp_query->set_404();
		}
		if ( $usetheme ) {
			$template = null;
			if ( function_exists( 'get_404_template' ) ) {
				$template = get_404_template();
			}
			if ( function_exists( 'apply_filters' ) ) {
				$template = apply_filters( 'hmw_404_template', $template );
			}
			if ( $template && @file_exists( $template ) ) {
				ob_start();
				include( $template );
				echo $this->find_replace( ob_get_clean() );
				exit;
			}
		}

		header( 'HTTP/1.0 404 Not Found', true, 404 );
		echo '<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL ' . esc_url( $_SERVER['REQUEST_URI'] ) . ' was not found on this server.</p></body></html>';
		exit;
	}

	/************************************* FIND AND REPLACE *****************************************/
	/**
	 * Prepare the replace function
	 *
	 * @param string $content
	 */
	public function prepareFindReplace( $content = '' ) {
		$findencoded = $findencodedfinal = $replaceencoded = $replaceencodedfinal = $findcdns = $replacecdns = array();

		if ( $cdns = HMW_Classes_ObjController::getClass( 'HMW_Models_Compatibility' )->findCDNServers() ) {
			foreach ( $cdns as $cdn ) {
				$cdn = parse_url( $cdn, PHP_URL_HOST ) . parse_url( site_url(), PHP_URL_PATH ) . '/';

				$findcdn    = preg_replace( '/^/', $cdn, (array) $this->_replace['from'] );
				$replacecdn = preg_replace( '/^/', $cdn, (array) $this->_replace['to'] );

				//merge the urls
				$findcdns    = array_merge( $findcdns, $findcdn );
				$replacecdns = array_merge( $replacecdns, $replacecdn );

				//HMW_Debug::dump($cdn, $findcdns, $replacecdns);
			}
		}

		if ( isset( $this->_replace['from'] ) && isset( $this->_replace['to'] ) && ! empty( $this->_replace['from'] ) && ! empty( $this->_replace['to'] ) ) {
			//make sure the paths are without schema
			$find    = array_map( array( $this, 'addDomainUrl' ), (array) $this->_replace['from'] );
			$replace = array_map( array( $this, 'addDomainUrl' ), (array) $this->_replace['to'] );

			//change the javascript urls
			$findencoded    = array_map( array( $this, 'changeEncodedURL' ), (array) $this->_replace['from'] );
			$replaceencoded = array_map( array( $this, 'changeEncodedURL' ), (array) $this->_replace['to'] );
			//change the javascript urls
			$findencodedfinal    = array_map( array(
				$this,
				'changeEncodedURLFinal'
			), (array) $this->_replace['from'] );
			$replaceencodedfinal = array_map( array( $this, 'changeEncodedURLFinal' ), (array) $this->_replace['to'] );
		}

		//merge the urls
		$this->_replace['from'] = array_merge( $findcdns, $find, $findencoded, $findencodedfinal );
		$this->_replace['to']   = array_merge( $replacecdns, $replace, $replaceencoded, $replaceencodedfinal );


	}

	/**
	 * Remove the Schema from url
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public function addDomainUrl( $url ) {
		if ( strpos( $url, $this->_blogurl ) === false ) {
			return $this->_blogurl . '/' . $url;
		}
	}

	/**
	 * Remove the Schema from url
	 * Return slashed urls for javascript urls
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public function changeEncodedURL( $url ) {
		if ( strpos( $url, $this->_blogurl ) === false ) {
			return str_replace( '/', '\/', $this->_blogurl . '/' . $url );
		}
	}

	/**
	 * @param $url
	 *
	 * @return mixed
	 */
	public function changeEncodedURLFinal( $url ) {
		if ( strpos( $url, $this->_blogurl ) === false ) {
			return str_replace( '/', '\/', rtrim( $this->_blogurl . '/' . $url, '/' ) );
		}
	}

	/**
	 * Change content
	 *
	 * @param string $content
	 *
	 * @return mixed
	 */
	public function find_replace( $content ) {
		if ( HMW_Classes_Tools::getOption( 'error' ) ) {
			return $content;
		}

		if ( is_string( $content ) ) {

			//if the changes were made already, return
			if ( strpos( $content, HMW_Classes_Tools::$default['hmw_wp-content_url'] ) === false && $this->_replaced ) {
				return $content;
			}

			//remove source commets
			if ( HMW_Classes_Tools::getOption( 'hmw_hide_comments' ) ) {
				$content = preg_replace_callback( '/<!--([\\s\\S]*?)-->/', array( $this, '_commentRemove' ), $content );
			}

			//remove versions
			if ( HMW_Classes_Tools::getOption( 'hmw_hide_version' ) ) {
				$content = preg_replace( array(
					'/[\?|&]ver=[0-9a-zA-Z\.\_\-\+]+/',
					'/<meta[^>]*name=[\'"]generator[\'"][^>]*>/i',
					'/<link[^>]*rel=[\'"]dns-prefetch[\'"][^>]*>/i'
				), '', $content );
				if ( defined( 'JETPACK__VERSION' ) ) {
					$content = preg_replace( '/<script[^>]*src=[\'"]https:\/\/s0.wp.com\/wp-content\/js\/devicepx-jetpack.js[\'"][^>]*><\/script>/i', '<script type="text/javascript" src="' . _HMW_THEME_URL_ . 'js/jptraffic.js' . '"></script>', $content );
				}
			}

			//if it wasn't replaced before
			if ( ! $this->_replaced ) {
				if ( ! isset( $this->_replace['from'] ) && ! isset( $this->_replace['to'] ) ) {
					$this->buildRedirect();
				}

				//fix the relative links if not in safe mode
				if ( HMW_Classes_Tools::getOption( 'hmw_fix_relative' ) ) {
					$content = $this->fixRelativeLinks( $content );
				}

				//make sure to include the blog url
				$this->prepareFindReplace( $content );
			}

			if ( isset( $this->_replace['from'] ) && isset( $this->_replace['to'] ) && ! empty( $this->_replace['from'] ) && ! empty( $this->_replace['to'] ) ) {
				$content = str_ireplace( $this->_replace['from'], $this->_replace['to'], $content );
			}

			//Replace custom classes
			$content = $this->replaceTextMapping( $content );

		}

		//Remove Powered-By header and link
		if ( function_exists( 'header_remove' ) ) {
			@header_remove( "X-Powered-By" );
			@header_remove( "x-powered-by" );
			@header_remove( "x-cf-powered-by" );
			@header_remove( "Server" );
			@header_remove( "server" );
		}

		$this->_replaced = true;

		return $content;
	}

	/**
	 * Rename the paths in URL with the new ones
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public function find_replace_url( $url ) {
		if ( strpos( $url, '/' . HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' ) !== false || strpos( $url, '/' . HMW_Classes_Tools::$default['hmw_wp-includes_url'] . '/' ) !== false ) {
			//change and replace paths
			if ( ! isset( $this->_replace['from'] ) && ! isset( $this->_replace['to'] ) ) {
				$this->buildRedirect();
			}

			if ( isset( $this->_replace['from'] ) && isset( $this->_replace['to'] ) && ! empty( $this->_replace['from'] ) && ! empty( $this->_replace['to'] ) ) {
				$rewrite      = $this->_replace['rewrite'];
				$rewrite_from = $this->_replace['from'];
				$rewrite_to   = $this->_replace['to'];
				foreach ( $rewrite as $index => $value ) {
					//add only the paths or the design path
					if ( ( $index && isset( $rewrite_to[ $index ] ) && substr( $rewrite_to[ $index ], - 1 ) == '/' ) ||
					     strpos( $rewrite_to[ $index ], '/' . HMW_Classes_Tools::getOption( 'hmw_themes_style' ) ) ) {
						$this->_replace['from'][] = $rewrite_from[ $index ];
						$this->_replace['to'][]   = $rewrite_to[ $index ];
					}
				}

				unset( $rewrite );
				unset( $rewrite_from );
				unset( $rewrite_to );

				//Don't replace include if content was already replaced
				$url = str_ireplace( $this->_replace['from'], $this->_replace['to'], $url );
			}
		}

		return $url;
	}

	/**
	 * Find the text from Text Mapping in the source code
	 *
	 * @param $content
	 *
	 * @return mixed|string|string[]|null
	 */
	public function replaceTextMapping( $content ) {
		$findtextmapping = array();

		//Replace custom classes
		$hmw_text_mapping = json_decode( HMW_Classes_Tools::getOption( 'hmw_text_mapping' ), true );
		if ( isset( $hmw_text_mapping['from'] ) && ! empty( $hmw_text_mapping['from'] ) &&
		     isset( $hmw_text_mapping['to'] ) && ! empty( $hmw_text_mapping['to'] ) ) {

			foreach ( $hmw_text_mapping['to'] as &$value ) {
				if ( $value <> '' ) {
					if ( strpos( $value, '{rand}' ) !== false ) {
						$value = str_replace( '{rand}', HMW_Classes_Tools::generateRandomString( 5 ), $value );
					} elseif ( strpos( $value, '{blank}' ) !== false ) {
						$value = str_replace( '{blank}', '', $value );
					}
				}
			}

			$this->_findtextmapping    = $hmw_text_mapping['from'];
			$this->_replacetextmapping = $hmw_text_mapping['to'];

			if ( HMW_Classes_Tools::getOption( 'hmw_mapping_classes' ) ) {
				foreach ( $this->_findtextmapping as $index => $from ) {
					$findtextmapping[] = '/\s(class|id|aria-labelledby|aria-controls)=[\'"][^\'"]*(' . addslashes( $from ) . ')[^\'"]*[\'"]/';
					$findtextmapping[] = "'<(style|script)((?!src|>).)*>.*?</(style|script)>'is";
					$findtextmapping[] = "'<(a|div)[^>]*data-" . addslashes( $from ) . "[^>]*[^/]>'is";
				}

				if ( ! empty( $findtextmapping ) ) {
					$content = preg_replace_callback( $findtextmapping, array(
						$this,
						'replaceText'
					), $content );
				}


			} else {
				$content = str_ireplace( $this->_findtextmapping, $this->_replacetextmapping, $content );
			}

			unset( $hmw_text_mapping );
		}

		return $content;
	}

	/**
	 * Callback for Text Mapping
	 *
	 * @param $found
	 *
	 * @return mixed
	 */
	public function replaceText( $found ) {
		$content = $found[0];
		if ( $content <> '' ) {
			$content = str_ireplace( $this->_findtextmapping, $this->_replacetextmapping, $content );
		}

		return $content;
	}

	/**
	 * Replace the author URL is changed
	 *
	 * @param $url
	 *
	 * @return mixed
	 */
	public function replace_author_url( $url ) {
		//Modify rest-api wp-json
		if ( HMW_Classes_Tools::$default['hmw_author_url'] <> HMW_Classes_Tools::getOption( 'hmw_author_url' ) ) {
			return str_replace( HMW_Classes_Tools::$default['hmw_author_url'], HMW_Classes_Tools::getOption( 'hmw_author_url' ), $url );
		}

		return $url;
	}

	/**
	 * Fix for Wp-Rocket plugin. Remove deferred option from Jquery
	 *
	 * @param $content
	 *
	 * @return null|string|string[]
	 */
	public function remove_jquery_deferred( $content ) {
		$content = preg_replace( '/<script[^>]*src=[\'"]([^\'"]*jquery.js)[\'"][^>]*defer[^>]*><\/script>/i', '<script type="text/javascript" src="$1"></script>', $content );

		return $content;
	}

	/**
	 * Change the image path to absolute when in feed
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function fixRelativeLinks( $content ) {
		$content = preg_replace_callback(
			'~(\s(href|src)\s*[=|:]\s*[\"\'])([^\"\']+)([\"\'])~i',
			array( $this, 'replaceLinks' ),
			$content );
		$content = preg_replace_callback(
			'~(\W(url\s*)[\(\"\']+)([^\)\"\']+)([\)\"\']+)~i',
			array( $this, 'replaceLinks' ),
			$content );
		$content = preg_replace_callback(
			'~(([\"\']url[\"\']\s*\:)\s*[\"\'])([^\"\']+)([\"\'])~i',
			array( $this, 'replaceLinks' ),
			$content );

		return $content;
	}

	/**
	 * If relative links then transform them to absolute
	 *
	 * @param $found
	 *
	 * @return string
	 */
	public function replaceLinks( $found ) {
		$url = $found[3];

		if ( strpos( $url, '//' ) === false && strpos( $url, '\/\/' ) === false ) {
			if ( strpos( $url, '/' . HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/' ) !== false
			     || strpos( $url, '/' . HMW_Classes_Tools::$default['hmw_wp-includes_url'] . '/' ) !== false
			     || strpos( $url, '/' . HMW_Classes_Tools::$default['hmw_admin_url'] ) !== false
			     || strpos( $url, '/' . HMW_Classes_Tools::$default['hmw_login_url'] ) !== false
			) {
				HMW_Debug::dump( $url );

				return $found[1] . $this->_rel2abs( $url ) . $found[4];
			}
		}


		return $found[0];
	}

	/**
	 * Change Relative links to Absolute links
	 *
	 * @param $rel
	 *
	 * @return string
	 */
	protected function _rel2abs( $rel ) {
		$scheme    = $host = $path = '';
		$backslash = false;

		// parse base URL  and convert to local variables: $scheme, $host,  $path
		extract( parse_url( site_url() ) );

		if ( strpos( $rel, "//" ) === 0 ) {
			return $scheme . ':' . $rel;
		}

		if ( strpos( $rel, '\/' ) !== false ) {
			//if backslashes then change the URLs to normal
			$backslash = true;
			$rel       = str_replace( '\/', '/', $rel );
		}

		// return if already absolute URL
		if ( parse_url( $rel, PHP_URL_SCHEME ) != '' ) {
			return $rel;
		}

		// queries and anchors
		if ( $rel[0] == '#' || $rel[0] == '?' ) {
			return site_url() . $rel;
		}

		// dirty absolute URL
		if ( $path <> '' && ( strpos( $rel, $path . '/' ) === false || strpos( $rel, $path . '/' ) > 0 ) ) {
			$abs = $host . $path . "/" . $rel;
		} else {
			$abs = $host . "/" . $rel;
		}

		// replace '//' or  '/./' or '/foo/../' with '/'
		$abs = preg_replace( "/(\/\.?\/)/", "/", $abs );
		$abs = preg_replace( "/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs );

		// absolute URL is ready!
		if ( $backslash ) {
			return str_replace( '/', '\/', $scheme . '://' . $abs );
		} else {
			return $scheme . '://' . $abs;

		}
	}

	/**
	 * Remove the comments from source code
	 *
	 * @param $m
	 *
	 * @return string
	 */
	protected function _commentRemove( $m ) {
		return ( 0 === strpos( $m[1], '[' ) || false !== strpos( $m[1], '<![' ) )
			? $m[0]
			: '';
	}

	/**
	 * Remove the page headers
	 */
	public function hideHeaders() {
		//Remove the Link from HTTP Header
		if ( HMW_Classes_Tools::getOption( 'hmw_hide_header' ) ) {
			header( sprintf( '%s: %s', 'Link', '<' . site_url() . '>; rel=shortlink' ) );

			if ( function_exists( 'header_remove' ) ) {
				@header_remove( "X-Powered-By" );
				@header_remove( "x-powered-by" );
				@header_remove( 'X-Cf-Powered-By' );
				@header_remove( 'x-cf-powered-by' );
				@header_remove( "Server" );
				@header_remove( "server" );
			}
		}
	}

	/**
	 * Replace the robotx file fo rsecurity
	 *
	 * @param string $content
	 */
	public function replace_robots( $content ) {
		$robots = '';
		if ( $content && $content <> '' ) {
			$rows = preg_split( '/\n/', $content );

			foreach ( $rows as $row ) {
				if ( strpos( $row, 'Sitemap:' ) !== false ) {
					$robots .= $row . PHP_EOL;

				}
			}
		}

		$robots .= PHP_EOL . 'User-agent: *' . PHP_EOL;

		if ( HMW_Classes_Tools::getOption( 'hmw_upload_url' ) <> HMW_Classes_Tools::$default['hmw_upload_url'] ) {
			$robots .= 'Allow: */' . HMW_Classes_Tools::getOption( 'hmw_upload_url' ) . '/';
		} else {
			$robots .= 'Allow: */' . HMW_Classes_Tools::getOption( 'hmw_wp-content_url' ) . '/' . HMW_Classes_Tools::$default['hmw_upload_url'] . '/';
		}

		header( 'Status: 200 OK', true, 200 );
		header( 'Content-type: text/plain; charset=' . get_bloginfo( 'charset' ) );

		echo $robots;
		exit();

	}

	/**
	 * Replace the Error Message that contains WordPress
	 *
	 * @param $message
	 * @param $error
	 *
	 * @return string|void
	 */
	public function replace_error_message( $message, $error ) {
		if ( is_protected_endpoint() ) {
			$message = __( 'There has been a critical error on your website. Please check your site admin email inbox for instructions.' );
		} else {
			$message = __( 'There has been a critical error on your website.' );
		}

		return $message;
	}

}