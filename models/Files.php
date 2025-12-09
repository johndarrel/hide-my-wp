<?php
/**
 * Files Handle Model
 * Called to handle the files when they are not found
 *
 * @file  The Files Handle file
 * @package HMWP/FilesModel
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Files {

	protected $_files = array();
	protected $_safe_files = array();
	protected $_replace = array();
	protected $_rewrites = array();

	/**
	 * Initializes the object by setting up the list of handled file extensions,
	 * safe file extensions, and the replacement array.
	 *
	 * @return void
	 */
	public function __construct() {
		// The list of handled file extensions
		$this->_files = array(
			'jpg',
			'jpeg',
			'png',
			'bmp',
			'gif',
			'jp2',
			'weba',
			'webp',
			'webm',
			'css',
			'scss',
			'js',
			'woff',
			'woff2',
			'ttf',
			'otf',
			'pfb',
			'pfm',
			'tfil',
			'eot',
			'svg',
			'pdf',
			'doc',
			'docx',
			'csv',
			'xls',
			'xslx',
			'mp2',
			'mp3',
			'mp4',
			'mpeg',
			'zip',
			'rar',
			'map'
		);

		// The safe extensions for static files
		//the safe extensions for static files
		$this->_safe_files = array_map(function($file) {
			return $file . 'h';
		}, $this->_files);

		// Init the replacement array
		$this->_replace = array( 'from' => [], 'to' => [] );
	}

	/**
	 * Determines if the current URL corresponds to a file and displays it if so.
	 *
	 * This method checks if the current URL points to a file, and if the file
	 * is managed by WordPress and has been modified by the plugin.
	 * If both conditions are met, it will display the file content.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function maybeShowFile() {
		//If the file is handled by WordPress
		//Show it if was changed by HMWP
		if ( $this->isFile( $this->getCurrentURL() ) ) {
			$this->showFile( $this->getCurrentURL() );
		}

	}

	/**
	 * Checks if a 404 error occurred and displays the appropriate content. If a 404 error is detected, it shows the file content for the current URL. Otherwise, it checks if a login page should be displayed.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function maybeShowNotFound() {
		//If the file doesn't exist
		//show the file content
		if ( is_404() ) {
			$this->showFile( $this->getCurrentURL() );
		} else {
			$this->maybeShowLogin( $this->getCurrentURL() );
		}

	}

	/**
	 * Check if the current path is the login path
	 *
	 * @param $url
	 *
	 * @return void
	 * @throws Exception
	 */
	public function maybeShowLogin( $url ) {
		// Remove queries from URL
		$url_no_query = ( ( strpos( $url, '?' ) !== false ) ? substr( $url, 0, strpos( $url, '?' ) ) : $url );

		if ( strpos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) . '/' ) ||
		     strpos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) . '/' ) ) {

			add_filter( 'hmwp_option_hmwp_remove_third_hooks', '__return_true' );

			header( "HTTP/1.1 200 OK" );

			$this->handleLogin( $url );

		}
	}

	/**
	 * If the rewrite config is not set
	 * If there is a new file path, change it back to real path and show the file
	 * Prevents errors when the paths are chnged but the rewrite config is not set up correctly
	 *
	 * @param $url
	 *
	 * @return bool|string
	 */
	public function isFile( $url ) {

		if ( $url <> '' ) {
			if ( strpos( $url, '?' ) !== false ) {
				$url = substr( $url, 0, strpos( $url, '?' ) );
			}
			if ( strrpos( $url, '.' ) !== false ) {
				$ext = substr( $url, strrpos( $url, '.' ) + 1 );
				if ( in_array( $ext, $this->_files ) || in_array( $ext, $this->_safe_files ) ) {
					return $ext;
				}
			}
		}

		return false;
	}

	/**
	 * Constructs the current URL based on server variables.
	 *
	 * @return string The full URL currently in the address bar.
	 */
	public function getCurrentURL() {
		$url = '';

		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			// build the URL in the address bar
			$url = is_ssl() ? 'https://' : 'http://';
			$url .= $_SERVER['HTTP_HOST'];
			if ( HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ) &&
			     HMWP_Classes_Tools::getValue( 'hmwp_url' ) ) {
				$url .= HMWP_Classes_Tools::getValue( 'hmwp_url' );
			} elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$url .= rawurldecode( $_SERVER['REQUEST_URI'] );
			}
		}

		return $url;
	}

	/**
	 * Builds the rewrite rules to map URLs back to their original paths based on stored mappings and replacements.
	 *
	 * @return void
	 * @throws Exception If there is an error in building the redirects.
	 */
	public function buildRedirect() {
		$rewriteModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' );

		// Build the rules paths to change back the hidden paths
		if ( empty( $rewriteModel->_replace ) ) {
			$rewriteModel->buildRedirect();
		}

		// URL Mapping
		$hmwp_url_mapping = json_decode( HMWP_Classes_Tools::getOption( 'hmwp_url_mapping' ), true );
		if ( isset( $hmwp_url_mapping['from'] ) && ! empty( $hmwp_url_mapping['from'] ) ) {
			foreach ( $hmwp_url_mapping['from'] as $index => $row ) {
				if ( substr( $hmwp_url_mapping['from'][ $index ], - 1 ) == '/' ) {
					$this->_rewrites['from'][] = '#^/' . str_replace( array( home_url() . '/' ), '', ltrim( $hmwp_url_mapping['to'][ $index ], '/' ) ) . '(.*)' . '#i';
					$this->_rewrites['to'][]   = '/' . str_replace( array( home_url() . '/' ), '', ltrim( $hmwp_url_mapping['from'][ $index ], '/' ) ) . "$1";
				} else {
					$this->_rewrites['from'][] = '#^/' . str_replace( array( home_url() . '/' ), '', ltrim( $hmwp_url_mapping['to'][ $index ], '/' ) ) . '$' . '#i';
					$this->_rewrites['to'][]   = '/' . str_replace( array( home_url() . '/' ), '', ltrim( $hmwp_url_mapping['from'][ $index ], '/' ) );
				}
			}
		}

		// Build the regex array to replace the path to original
		if ( ! empty( $rewriteModel->_replace['from'] ) && ! empty( $rewriteModel->_replace['to'] ) ) {
			foreach ( $rewriteModel->_replace['from'] as $index => $row ) {
				$this->_rewrites['from'][] = '#^/' . $rewriteModel->_replace['to'][ $index ] . ( substr( $rewriteModel->_replace['to'][ $index ], - 1 ) == '/' ? "(.*)" : "" ) . '#i';
				$this->_rewrites['to'][]   = '/' . $rewriteModel->_replace['from'][ $index ] . ( substr( $rewriteModel->_replace['to'][ $index ], - 1 ) == '/' ? "$1" : "" );
			}
		}

	}

	/**
	 * Retrieves the original URL by applying rewrite rules and constructing the URL from parsed components.
	 *
	 * @param  string  $url  The redirected URL which needs to be converted back to the original URL.
	 *
	 * @return string The original URL reconstructed from the given URL based on rewrite rules.
	 * @throws Exception
	 */
	public function getOriginalUrl( $url ) {

		// Build the rewrite rules if they are not already built
		if ( empty( $this->_rewrites ) ) {
			$this->buildRedirect();
		}

		// Parse the URL components
		$parse_url = wp_parse_url( $url );

		// Only if there is a path to change
		if ( ! isset( $parse_url['host'] ) || ! isset( $parse_url['path'] )) {
			return $url;
		}

		// Get the home root path
		$path = wp_parse_url( home_url(), PHP_URL_PATH );

		// Backslash the paths
		if ( $path <> '' ) {
			$parse_url['path'] = preg_replace( '/^' . preg_quote( $path, '/' ) . '/', '', $parse_url['path'] );
		}

		// Replace paths to original based on rewrite rules
		if ( isset( $this->_rewrites['from'] ) && isset( $this->_rewrites['to'] ) && ! empty( $this->_rewrites['from'] ) && ! empty( $this->_rewrites['to'] ) ) {
			$parse_url['path'] = preg_replace( $this->_rewrites['from'], $this->_rewrites['to'], $parse_url['path'], 1 );
		}

		// Default to https if the scheme is not set
		if ( ! isset( $parse_url['scheme'] ) ) {
			$parse_url['scheme'] = 'https';
		}

		// Reconstruct the URL
		if ( isset( $parse_url['port'] ) && $parse_url['port'] <> 80 ) {
			$new_url = $parse_url['scheme'] . '://' . $parse_url['host'] . ':' . $parse_url['port'] . $path . $parse_url['path'];
		} else {
			$new_url = $parse_url['scheme'] . '://' . $parse_url['host'] . $path . $parse_url['path'];
		}

		// Append query string if present
		if ( isset( $parse_url['query'] ) && ! empty( $parse_url['query'] ) ) {
			$query   = $parse_url['query'];
			$query   = str_replace( array( '?', '%3F' ), '&', $query );
			$new_url .= ( ! strpos( $new_url, '?' ) ? '?' : '&' ) . $query;
		}

		// Return the constructed URL
		return sanitize_url( $new_url );

	}

	/**
	 * Get the original path from url
	 *
	 * @param  $new_url
	 *
	 * @return string
	 */
	public function getOriginalPath( $new_url ) {
		//remove domain from path
		$new_path = str_replace( home_url(), '', $new_url );

		//remove queries from path
		if ( strpos( $new_path, '?' ) !== false ) {
			$new_path = substr( $new_path, 0, strpos( $new_path, '?' ) );
		}

		$new_path = realpath( HMWP_Classes_Tools::getRootPath() . ltrim( $new_path, '/' ) );
		$new_path = str_replace( '\\', '/', $new_path );

		if ( strpos( $new_path, HMWP_Classes_Tools::getRootPath() ) === false ) {
			return false;
		}

		return $new_path;
	}

	/**
	 * Return the file mime based on extension
	 *
	 * @param $ext
	 *
	 * @return false|string
	 */
	private function getMime( $ext ) {

		switch ( $ext ) {
			case "scss":
			case "csv":
			case "css":
				return "text/css";
			case "js":
			case "mjs":
				return "text/javascript";
			case "svg":
				return "image/svg+xml";
			case "jpg":
				return "image/jpeg";
			case "jpeg":
			case "png":
			case "bmp":
			case "gif":
			case "jp2":
			case "tiff":
			case "webp":
			case "avif":
				return "image/" . $ext;
			case "ico":
			case "icon":
				return "image/vnd.microsoft.icon";
			case "woff":
			case "woff2":
			case "ttf":
			case "otf":
				return "font/" . $ext;
			case "eot":
				return "application/vnd.ms-fontobject";
			case "avi":
				return "video/x-msvideo";
			case "mp4":
			case "mpeg":
			case "webm":
				return "video/" . $ext;
			case "doc":
				return "application/msword";
			case "xls":
				return "application/vnd.ms-excel";
			case "json":
				return "application/json";
			case "docx":
				return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
			case "xlsx":
				return "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
			case "xml":
			case "zip":
				return "application/" . $ext;
		}

		return false;
	}

	/**
	 * Show the file when the server rewrite is not added
	 *
	 * @param  string  $url  broken URL
	 *
	 * @throws Exception
	 */
	public function showFile( $url ) {

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		// Remove the redirect hook
		remove_filter( 'wp_redirect', array( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' ), 'sanitize_redirect' ), PHP_INT_MAX );
		remove_filter( 'template_directory_uri', array( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' ), 'find_replace_url' ), PHP_INT_MAX );

		// Build the rewrite rules
		$this->buildRedirect();

		//Get the original URL and path based on rewrite rules
		$url_no_query = ( ( strpos( $url, '?' ) !== false ) ? substr( $url, 0, strpos( $url, '?' ) ) : $url );
		$new_url          = $this->getOriginalUrl( $url );
		$new_url_no_query = ( ( strpos( $new_url, '?' ) !== false ) ? substr( $new_url, 0, strpos( $new_url, '?' ) ) : $new_url );
		$new_path         = $this->getOriginalPath( $new_url );
		$mime            = false;

		// Hook the original url/path when handles by WP
		do_action( 'hmwp_files_show_file', $new_url, $new_path );

		// If there is a mapping in the current URL
		if ( $url <> $new_url ) {

			// If it's a file type
			if ( $ext = $this->isFile( $new_url ) ) {

				// If the file exists on the server
				if ( $new_path && $wp_filesystem->exists( $new_path ) ) {

					// If the plugin is not set to map all the files dynamically
					if ( ! HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ) ) {
						// If file is loaded through WordPress rewrites and not through config file
						if ( wp_parse_url( $url ) && in_array( $ext, array( 'png', 'jpg', 'jpeg', 'webp', 'gif') ) ) {
							if ( stripos( $new_url, 'wp-admin' ) === false ) {
								// If it's a valid URL
								// add the url in the WP rewrite list
								$mappings = (array) HMWP_Classes_Tools::getOption( 'file_mappings' );
								if ( count( $mappings ) < 10 ) {
									$mappings[ md5( $url ) ] = $url;
									HMWP_Classes_Tools::saveOptions( 'file_mappings', $mappings );
								}

								// For debugging
								do_action( 'hmwp_debug_files', $url );
							}
						}

					}

					//////////////////////////////////////////////////////////////////////////

					if ( ! $mime = $this->getMime( $ext ) ) {
						if ( function_exists( 'mime_content_type' ) ) {
							$mime = @mime_content_type( $new_path );
						} else {
							$mime = 'text/plain';
						}
					}

					//////////////////////////////////////////////////////////////////////////

					ob_clean(); //clear the buffer
					$content = $wp_filesystem->get_contents( $new_path );
					$etag    = md5_file( $new_path );

					if ( function_exists( 'http_response_code' ) ) {
						http_response_code( 200 );
					}

					header( "HTTP/1.1 200 OK" );
					header( "Cache-Control: max-age=2592000, must-revalidate" );
					header( "Expires: " . gmdate( 'r', strtotime( "+1 month" ) ) );
					header( "Vary: Accept-Encoding" );
					header( "Pragma: public" );
					header( "Etag: \"{$etag}\"" );

					if ( $mime ) {
						header( 'Content-Type: ' . $mime . '; charset: UTF-8' );
					}

					//////////////////////////////////////////////////////////////////////////
					// If CSS, JS or SCSS
					if ( strpos( $new_url, '.js' ) || strpos( $new_url, '.css' ) || strpos( $new_url, '.scss' ) ) {

						// URL Mapping for all css and js files
						$content = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->find_replace_url( $content );
						// Text Mapping for all css and js files
						$content = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->replaceTextMapping( $content, true );

						// Cache the CSS and JS files if no cache plugin is installed
						if ( function_exists( 'brotli_compress' ) ) {
							// Brotli the CSS, JS
							header( "Content-Encoding: br" );
							$content = brotli_compress( $content, 1 );
						} elseif ( function_exists( 'gzcompress' ) ) {
							// deflate the  CSS, JS
							header( "Content-Encoding: deflate" );
							$content = gzcompress( $content );
						} elseif ( function_exists( 'gzencode' ) ) {
							// Gzip the  CSS, JS
							header( "Content-Encoding: gzip" );
							$content = gzencode( $content );
						}

						// Show the file html content
						header( 'Content-Length: ' . strlen( $content ) );
					}

					echo $content;
					exit();
				}

			} elseif ( stripos( trailingslashit( $new_url_no_query ), '/' . HMWP_Classes_Tools::getDefault( 'hmwp_wp-json' ) . '/' ) !== false ) {

				$response = false;

				if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
					$response = $this->postRequest( $new_url );
				} elseif ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'GET' ) {
					$response = $this->getRequest( $new_url );
				}

				if ( $response ) {
					header( "HTTP/1.1 200 OK" );
					if ( ! empty( $response['headers'] ) ) {
						foreach ( $response['headers'] as $header ) {
							header( $header );
						}
					}

					//Echo the html file content
					echo $response['body'];
					exit();
				}

				exit();

			} elseif ( stripos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) . '/' ) !== false ||
			           ( HMWP_Classes_Tools::getOption( 'hmwp_logout_url' ) <> '' && stripos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getOption( 'hmwp_logout_url' ) . '/' ) !== false ) ||
			           ( HMWP_Classes_Tools::getOption( 'hmwp_lostpassword_url' ) <> '' && stripos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getOption( 'hmwp_lostpassword_url' ) . '/' ) !== false ) ||
			           ( HMWP_Classes_Tools::getOption( 'hmwp_register_url' ) <> '' && stripos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getOption( 'hmwp_register_url' ) . '/' ) !== false ) ||
			           stripos( trailingslashit( $new_url_no_query ), '/' . HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) . '/' ) !== false ) {

				add_filter( 'hmwp_option_hmwp_remove_third_hooks', '__return_true' );

				header( "HTTP/1.1 200 OK" );

				$this->handleLogin( $new_url );

			} elseif ( stripos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) . '/' ) !== false ||
			           stripos( trailingslashit( $new_url_no_query ), '/' . HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) . '/' ) !== false ) {

				wp_safe_redirect( $new_url, 301 );
				exit();

			} elseif (  HMWP_Classes_Tools::isMultisites() && stripos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getOption( 'hmwp_activate_url' ) . '/' ) !== false ) {

				if ( $new_path && strpos( $new_path, 'wp-activate.php' ) && $wp_filesystem->exists( $new_path ) ) {
					header( "HTTP/1.1 200 OK" );

					ob_start();
					global $wp_object_cache, $wp_query;
					require_once $new_path;
					$content = ob_get_clean();

					//Echo the html file content
					echo $content;
					die();
				}

			}

		}
	}

	/**
	 * Do a Post request
	 *
	 * @param  $url
	 *
	 * @return array
	 */
	public function postRequest( $url ) {
		$return = array();

		$headers = getallheaders();
		$options = array(
			'method'    => 'POST',
			'headers'   => $headers,
			'body'      => $_POST,
			'timeout'   => 60,
			'sslverify' => false,
		);

		do_action( 'hmwp_files_post_request_before', $url, $options );

		$response = wp_remote_post( $url, $options );

		$return['body'] = wp_remote_retrieve_body( $response );
		foreach ( wp_remote_retrieve_headers( $response ) as $key => $value ) {
			if ( ! is_array( $value ) ) {
				$return['headers'][] = "$key: $value";
			} else {
				foreach ( $value as $v ) {
					$return['headers'][] = "$key: $v";
				}
			}
		}

		do_action( 'hmwp_files_post_request_after', $url, $return );

		return $return;
	}

	/**
	 * Do a Get request
	 *
	 * @param  $url
	 *
	 * @return array
	 */
	public function getRequest( $url ) {
		$return = array();

		$headers = getallheaders();
		$options = array(
			'method'    => 'GET',
			'headers'   => $headers,
			'timeout'   => 60,
			'sslverify' => false,
		);

		do_action( 'hmwp_files_get_request_before', $url, $options );

		$response = wp_remote_get( $url, $options );

		$return['body'] = wp_remote_retrieve_body( $response );
		foreach ( wp_remote_retrieve_headers( $response ) as $key => $value ) {
			if ( ! is_array( $value ) ) {
				$return['headers'][] = "$key: $value";
			} else {
				foreach ( $value as $v ) {
					$return['headers'][] = "$key: $v";
				}
			}
		}

		do_action( 'hmwp_files_get_request_after', $url, $return );

		return $return;
	}

	/**
	 * Look into array of actions
	 *
	 * @param $haystack
	 * @param  array  $needles
	 * @param  int  $offset
	 *
	 * @return bool|mixed
	 */
	function strposa( $haystack, $needles = array(), $offset = 0 ) {
		foreach ( $needles as $needle ) {
			if ( strpos( $haystack, $needle, $offset ) !== false ) {
				return $needle;
			}
		}

		return false;
	}


	/**
	 * Handle the Login if the rules were not added in the config file
	 *
	 * @param $url
	 *
	 * @return void
	 * @throws Exception
	 */
	public function handleLogin( $url ) {
		$url = rawurldecode( $url );

		if ( ! ( HMWP_Classes_Tools::getvalue( 'action' ) === 'postpass' && HMWP_Classes_Tools::getIsset( 'post_password' ) ) ) {

			//If it's the login page
			if ( strpos( $url, '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) ) || strpos( $url, '/' . HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) ) || ( HMWP_Classes_Tools::getOption( 'hmwp_lostpassword_url' ) && strpos( $url, '/' . HMWP_Classes_Tools::getOption( 'hmwp_lostpassword_url' ) ) ) || ( HMWP_Classes_Tools::getOption( 'hmwp_register_url' ) && strpos( $url, '/' . HMWP_Classes_Tools::getOption( 'hmwp_register_url' ) ) ) ) {

				do_action( 'hmwp_files_handle_login', $url );

				//Get the action if exists in params
				$params = array();
				$query  = wp_parse_url( $url, PHP_URL_QUERY );
				if ( $query <> '' ) {
					parse_str( $query, $params );
				}

				if ( isset( $params['action'] ) ) {
					$actions            = array(
						'postpass',
						'logout',
						'lostpassword',
						'retrievepassword',
						'resetpass',
						'rp',
						'register',
						'login',
						'confirmaction',
						'validate_2fa',
						'itsec-2fa',
					);
					$_REQUEST['action'] = $this->strposa( $params['action'], $actions );
				}

				$urled_redirect_to = '';
				if ( isset( $_REQUEST['redirect_to'] ) ) {
					$urled_redirect_to = $_REQUEST['redirect_to'];
				}

				//if user is logged in
				if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
					if ( HMWP_Classes_Tools::getOption( 'hmwp_logged_users_redirect' ) ) {
						/** @var HMWP_Models_Rewrite $rewriteModel */
						$rewriteModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' );
						$rewriteModel->dashboard_redirect();
					}
				}

				global $wp_query, $error, $interim_login, $action, $user_login;

				$wp_query->is_404 = false;
				if( ! empty($error) ) {
					$error = false;
				}

				require_once ABSPATH . 'wp-login.php';
				die();

			} elseif ( HMWP_Classes_Tools::getOption( 'hmwp_logout_url' ) <> '' && strpos( $url, '/' . HMWP_Classes_Tools::getOption( 'hmwp_logout_url' ) ) ) {

				check_admin_referer( 'log-out' );

				do_action( 'hmwp_files_handle_logout', $url );

				$user = wp_get_current_user();

				wp_logout();

				if ( ! empty( $_REQUEST['redirect_to'] ) ) {
					$redirect_to           = $_REQUEST['redirect_to'];
					$requested_redirect_to = $redirect_to;
				} else {
					$redirect_to = add_query_arg( array( 'loggedout' => 'true', 'wp_lang'   => get_user_locale( $user ), ), wp_login_url() );
					$requested_redirect_to = '';
				}

				$redirect_to = apply_filters( 'logout_redirect', $redirect_to, $requested_redirect_to, $user );

				wp_safe_redirect( $redirect_to );
				exit;
			}

		}

	}

}
