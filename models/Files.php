<?php
/**
 * Files Handle Model
 * Called to handle the files when they are not found
 *
 * @file  The Files Handle file
 * @package HMWP/FilesModel
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Files {

	protected $_files = array();
	protected $_safe_files = array();
	protected $_replace = array();
	protected $_rewrites = array();
	protected $_site_url = null;
	protected $_site_hosts = array();
	protected $_fingerprint = null;
	protected $_cachedir = null;

	public function __construct() {
		//The list of handled file extensions
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

		//the safe extensions for static files
		$this->_safe_files = array_map( function ( $file ) {
			return $file . 'h';
		}, $this->_files );

		//init the replacement array
		$this->_replace = array( 'from' => [], 'to' => [] );
	}

	/**
	 * Show the file if in the list of extensions
	 *
	 * @throws Exception
	 */
	public function maybeShowFile() {
		//If WordPress handles the file
		//Show it if was changed by HMWP
		if ( $this->isFile( $this->getCurrentURL() ) ) {
			$this->showFile( $this->getCurrentURL() );
		}

	}

	/**
	 * Check if the current URL is a file
	 *
	 * @throws Exception
	 */
	public function maybeShowNotFound() {
		// If the file doesn't exist,
		// show the file content
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
	 */
	public function maybeShowLogin( $url ) {
		//Remove the query from URL
		$url_no_query = ( ( strpos( $url, '?' ) !== false ) ? substr( $url, 0, strpos( $url, '?' ) ) : $url );

		if ( strpos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) . '/' ) || strpos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) . '/' ) ) {

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
	 * Get the current URL
	 *
	 * @return string
	 */
	public function getCurrentURL() {
		$url = '';

		// Use the site host, the Host header is set by the client
		if ( ! isset( $this->_site_url ) ) {
			$home            = wp_parse_url( home_url() );
			$this->_site_url = ( isset( $home['host'] ) && $home['host'] <> '' ? set_url_scheme( 'http://' . $home['host'] . ( isset( $home['port'] ) && $home['port'] ? ':' . (int) $home['port'] : '' ) ) : '' );
		}

		if ( $this->_site_url <> '' ) {
			// build the URL in the address bar
			$url = $this->_site_url;
			if ( HMWP_Classes_Tools::getOption( 'hmwp_mapping_text_show' ) &&
			     HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ) &&
			     HMWP_Classes_Tools::getValue( 'hmwp_url' ) ) {
				$url .= HMWP_Classes_Tools::getValue( 'hmwp_url' );
			} elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$url .= rawurldecode( wp_unslash( $_SERVER['REQUEST_URI']) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		}

		return $url;
	}

	/**
	 * Get the host and the port of an URL in a comparable format
	 *
	 * @param  string  $url  The URL to extract the authority from
	 *
	 * @return string The lowercase host, with the port appended when it's not the default one
	 */
	public function getUrlAuthority( $url ) {

		$parse_url = wp_parse_url( $url );

		if ( ! isset( $parse_url['host'] ) || $parse_url['host'] == '' ) {
			return '';
		}

		$authority = strtolower( $parse_url['host'] );

		if ( isset( $parse_url['port'] ) && $parse_url['port'] && ! in_array( (int) $parse_url['port'], array( 80, 443 ), true ) ) {
			$authority .= ':' . (int) $parse_url['port'];
		}

		return $authority;
	}

	/**
	 * Check if the URL points to the current site
	 *
	 * @param  string  $url  The URL to verify
	 *
	 * @return bool
	 */
	public function isSiteUrl( $url ) {

		$authority = $this->getUrlAuthority( $url );

		if ( $authority == '' ) {
			return false;
		}

		// The site hosts can't change during the request
		if ( empty( $this->_site_hosts ) ) {

			$allowed = array();

			foreach ( array( home_url(), site_url(), network_home_url(), network_site_url() ) as $site_url ) {
				if ( $site_authority = $this->getUrlAuthority( $site_url ) ) {
					$allowed[] = $site_authority;
				}
			}

			$this->_site_hosts = (array) apply_filters( 'hmwp_files_allowed_hosts', array_unique( $allowed ) );
		}

		return in_array( $authority, $this->_site_hosts, true );
	}

	/**
	 * Get the current request headers which can be forwarded to the site
	 *
	 * @return array
	 */
	public function getForwardHeaders() {

		$headers = array();

		if ( function_exists( 'getallheaders' ) ) {
			$headers = (array) getallheaders();
		} else {
			foreach ( $_SERVER as $key => $value ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( strpos( $key, 'HTTP_' ) === 0 ) {
					$name             = str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $key, 5 ) ) ) ) );
					$headers[ $name ] = wp_unslash( $value );
				}
			}
		}

		// Drop the Host header so the client can't pick the vhost of the internal request
		$skip = array(
			'host',
			'connection',
			'keep-alive',
			'proxy-authenticate',
			'proxy-authorization',
			'te',
			'trailer',
			'transfer-encoding',
			'upgrade',
			'content-length',
			'accept-encoding',
		);

		foreach ( $headers as $name => $value ) {
			if ( in_array( strtolower( $name ), $skip, true ) ) {
				unset( $headers[ $name ] );
			}
		}

		return apply_filters( 'hmwp_files_request_headers', $headers );
	}

	/**
	 * Check if a response header can be sent back to the browser
	 *
	 * @param  string  $name  The header name
	 *
	 * @return bool
	 */
	public function isAllowedHeader( $name ) {

		// The body is already decoded by WordPress so the hop-by-hop headers would break the response
		$skip = apply_filters( 'hmwp_files_skip_headers', array(
			'content-encoding',
			'content-length',
			'transfer-encoding',
			'connection',
			'keep-alive',
			'te',
			'trailer',
			'upgrade',
			'proxy-authenticate',
			'proxy-authorization',
		) );

		return ! in_array( strtolower( $name ), (array) $skip, true );
	}

	/**
	 * Get the response headers which can be sent back to the browser
	 *
	 * @param  array|WP_Error  $response  The remote response
	 *
	 * @return array
	 */
	public function getResponseHeaders( $response ) {

		$headers = array();

		foreach ( wp_remote_retrieve_headers( $response ) as $key => $value ) {

			if ( ! $this->isAllowedHeader( $key ) ) {
				continue;
			}

			if ( ! is_array( $value ) ) {
				$headers[] = "$key: $value";
			} else {
				foreach ( $value as $v ) {
					$headers[] = "$key: $v";
				}
			}
		}

		return apply_filters( 'hmwp_files_response_headers', $headers, $response );
	}

	/**
	 * Fingerprint of everything that changes the mapped file content
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getMappingFingerprint() {

		if ( ! isset( $this->_fingerprint ) ) {

			/** @var HMWP_Models_Rewrite $rewriteModel */
			$rewriteModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' );

			// Build the map the same way find_replace_url does it
			if ( empty( $rewriteModel->_replace ) ) {
				$rewriteModel->buildRedirect();
				$rewriteModel->prepareFindReplace();
			}

			// The map holds the CDN and the third party changes, so it covers more than the plugin options
			$this->_fingerprint = md5( wp_json_encode( array(
				$rewriteModel->_replace,
				HMWP_Classes_Tools::getOption( 'hmwp_text_mapping' ),
				HMWP_Classes_Tools::getOption( 'hmwp_mapping_classes' ),
				HMWP_Classes_Tools::getOption( 'hmwp_mapping_text_show' ),
				HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ),
				HMWP_Classes_Tools::isLoggedInUser(),
				( is_multisite() ? get_current_blog_id() : 0 ),
			) ) );
		}

		return $this->_fingerprint;
	}

	/**
	 * Build the cache validator for a file
	 *
	 * @param  string  $path  The file path on the server
	 * @param  bool  $mapped  True when the content is changed before it's sent
	 *
	 * @return array The etag and the last modified time
	 * @throws Exception
	 */
	public function getFileValidator( $path, $mapped = false ) {

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		$mtime = (int) $wp_filesystem->mtime( $path );
		$size  = (int) $wp_filesystem->size( $path );

		// The mapped content changes with the settings, add them in the validator
		$fingerprint = ( $mapped ? $this->getMappingFingerprint() : '' );

		return array(
			'etag'  => md5( $path . '|' . $mtime . '|' . $size . '|' . $fingerprint ),
			'mtime' => $mtime,
			'size'  => $size,
		);
	}

	/**
	 * Check if the browser already has the current version of the file
	 *
	 * @param  string  $etag  The current etag
	 * @param  int  $mtime  The last modified time
	 *
	 * @return bool
	 */
	public function isNotModified( $etag, $mtime ) {

		// The etag has priority over the modified time
		if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$match = trim( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( $match <> '' ) {
				// The browser can send more etags and the W/ prefix for the weak validators
				foreach ( explode( ',', $match ) as $value ) {
					$value = trim( str_replace( array( 'W/', '"' ), '', $value ) );
					if ( $value <> '' && $value === $etag ) {
						return true;
					}
				}

				return false;
			}
		}

		if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) && $mtime > 0 ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$since = strtotime( trim( wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( $since !== false && $mtime <= $since ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the compression accepted by the browser
	 *
	 * @return string br, gzip or empty when the content is sent as it is
	 */
	public function getAcceptedEncoding() {

		// Don't compress twice when the server already does it
		if ( ini_get( 'zlib.output_compression' ) ) {
			return '';
		}

		if ( ! isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return '';
		}

		$accepted = array();

		foreach ( explode( ',', strtolower( wp_unslash( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) ) as $value ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$parts  = explode( ';', $value );
			$name   = trim( $parts[0] );
			$weight = 1;

			// Skip the encodings the browser refuses with q=0
			if ( isset( $parts[1] ) && strpos( $parts[1], 'q=' ) !== false ) {
				$weight = (float) str_replace( 'q=', '', trim( $parts[1] ) );
			}

			if ( $name <> '' && $weight > 0 ) {
				$accepted[] = $name;
			}
		}

		if ( in_array( 'br', $accepted, true ) && function_exists( 'brotli_compress' ) ) {
			return 'br';
		}

		if ( in_array( 'gzip', $accepted, true ) && function_exists( 'gzencode' ) ) {
			return 'gzip';
		}

		return '';
	}

	/**
	 * Get the directory where the changed CSS and JS files are kept
	 *
	 * It's never under wp-content/cache, that directory is scanned and rewritten
	 * by checkCacheFiles when no known cache plugin is installed.
	 *
	 * @return string The cache directory with trailing slash, empty when it can't be used
	 */
	public function getCacheDir() {

		if ( isset( $this->_cachedir ) ) {
			return $this->_cachedir;
		}

		$this->_cachedir = '';

		$uploads = wp_upload_dir( null, false );

		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return $this->_cachedir;
		}

		$dir = trailingslashit( $uploads['basedir'] ) . 'wp-ghost-cache/';
		$dir = apply_filters( 'hmwp_files_cache_dir', $dir );

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( ! $wp_filesystem->is_dir( $dir ) ) {

			if ( ! $wp_filesystem->mkdir( $dir, HMW_DIR_PERMISSION ) ) {
				return $this->_cachedir;
			}

			// Keep the directory private
			$wp_filesystem->put_contents( $dir . 'index.php', '<?php // Silence is golden', HMW_FILE_PERMISSION );
			$deny = "<IfModule mod_authz_core.c>" . PHP_EOL . "Require all denied" . PHP_EOL . "</IfModule>" . PHP_EOL;
			$deny .= "<IfModule !mod_authz_core.c>" . PHP_EOL . "Order Allow,Deny" . PHP_EOL . "Deny from all" . PHP_EOL . "</IfModule>" . PHP_EOL;

			$wp_filesystem->put_contents( $dir . '.htaccess', $deny, HMW_FILE_PERMISSION );
		}

		if ( ! $wp_filesystem->is_writable( $dir ) ) {
			return $this->_cachedir;
		}

		$this->_cachedir = $dir;

		return $this->_cachedir;
	}

	/**
	 * Check if the changed content of a file can be kept on disk
	 *
	 * @return bool
	 */
	public function isCacheable() {

		// The content is not changed for logged in users, caching it would
		// send the original paths to the visitors
		if ( HMWP_Classes_Tools::isLoggedInUser() ) {
			return false;
		}

		// A random text mapping is generated on every request by design
		$mapping = json_decode( HMWP_Classes_Tools::getOption( 'hmwp_text_mapping' ), true );

		if ( isset( $mapping['to'] ) && is_array( $mapping['to'] ) ) {
			foreach ( $mapping['to'] as $value ) {
				if ( strpos( (string) $value, '{rand}' ) !== false ) {
					return false;
				}
			}
		}

		return (bool) apply_filters( 'hmwp_files_cache', true );
	}

	/**
	 * Get the file which holds the changed content
	 *
	 * @param  string  $etag  The validator of the original file
	 * @param  string  $encoding  The compression used for the content
	 *
	 * @return string The path of the cache file, empty when it can't be used
	 */
	public function getCacheFile( $etag, $encoding ) {

		if ( ! $dir = $this->getCacheDir() ) {
			return '';
		}

		// The extension is never css or js, so the cache rewriting never picks these files up
		return $dir . md5( $etag . '|' . $encoding ) . '.cache';
	}

	/**
	 * Save the changed content so it's not rebuilt on every request
	 *
	 * @param  string  $file  The cache file path
	 * @param  string  $content  The content to save
	 *
	 * @return void
	 */
	public function saveCacheFile( $file, $content ) {

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		// Write in a temporary file first so a visitor never reads half a file
		$temp = $file . '.' . wp_rand( 100000, 999999 ) . '.tmp';

		if ( $wp_filesystem->put_contents( $temp, $content, HMW_FILE_PERMISSION ) ) {
			if ( ! @rename( $temp, $file ) ) { //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$wp_filesystem->delete( $temp );
			}
		}

		$this->cleanCacheDir();
	}

	/**
	 * Remove the cache files left behind when a file or a setting changed
	 *
	 * @return void
	 */
	public function cleanCacheDir() {

		if ( ! $dir = $this->getCacheDir() ) {
			return;
		}

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		$marker = $dir . 'last-clean';
		$lifetime = (int) apply_filters( 'hmwp_files_cache_lifetime', WEEK_IN_SECONDS );

		// Only look through the directory once a day
		if ( $wp_filesystem->exists( $marker ) && ( time() - (int) $wp_filesystem->mtime( $marker ) ) < DAY_IN_SECONDS ) {
			return;
		}

		$wp_filesystem->put_contents( $marker, (string) time(), HMW_FILE_PERMISSION );

		if ( ! function_exists( 'glob' ) ) {
			return;
		}

		if ( $files = glob( $dir . '*.cache' ) ) {
			foreach ( $files as $file ) {
				if ( ( time() - (int) $wp_filesystem->mtime( $file ) ) > $lifetime ) {
					$wp_filesystem->delete( $file );
				}
			}
		}

		// Clean up the temporary files left by an interrupted write
		if ( $files = glob( $dir . '*.tmp' ) ) {
			foreach ( $files as $file ) {
				if ( ( time() - (int) $wp_filesystem->mtime( $file ) ) > HOUR_IN_SECONDS ) {
					$wp_filesystem->delete( $file );
				}
			}
		}
	}

	/**
	 * Close all the output buffers before sending a file
	 *
	 * @return void
	 */
	public function closeBuffers() {

		while ( ob_get_level() ) {
			ob_end_clean();
		}
	}

	/**
	 * Build the redirects array
	 *
	 * @throws Exception
	 */
	public function buildRedirect() {
		$rewriteModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' );
		//build the rules paths to change back the hidden paths
		$rewriteModel->clearRedirect()->buildRedirect();

		//URL Mapping
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

		// Never map an URL which doesn't belong to this site
		if ( ! $this->isSiteUrl( $url ) ) {
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
		remove_filter( 'wp_redirect', array(
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' ),
			'sanitize_redirect'
		), PHP_INT_MAX );
		remove_filter( 'template_directory_uri', array(
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' ),
			'find_replace_url'
		), PHP_INT_MAX );

		// Build the rewrite rules
		if ( empty( $this->_rewrites ) ) {
			$this->buildRedirect();
		}

		//Get the original URL and path based on rewrite rules
		$url_no_query     = ( ( strpos( $url, '?' ) !== false ) ? substr( $url, 0, strpos( $url, '?' ) ) : $url );
		$new_url          = $this->getOriginalUrl( $url );
		$new_url_no_query = ( ( strpos( $new_url, '?' ) !== false ) ? substr( $new_url, 0, strpos( $new_url, '?' ) ) : $new_url );
		$new_path         = $this->getOriginalPath( $new_url );
		$mime             = false;

		//hook the original url/path when handles by WP
		do_action( 'hmwp_files_show_file', $new_url, $new_path );

		// If there is a mapping in the current URL
		if ( $url <> $new_url ) {

			// If it's a file type
			if ( $ext = $this->isFile( $new_url ) ) {

				//if the file exists on the server
				if ( $new_path && $wp_filesystem->exists( $new_path ) ) {

					//If the plugin is not set to map all the files dynamically
					if ( ! HMWP_Classes_Tools::getOption( 'hmwp_mapping_file' ) ) {
						//if file is loaded through WordPress rewrites and not through config file
						if ( wp_parse_url( $url ) && in_array( $ext, array( 'png', 'jpg', 'jpeg', 'webp', 'gif', 'css', 'js', 'svg', 'woff', 'woff2' ) ) ) {
							if ( stripos( $new_url, 'wp-admin' ) === false ) {
								//if it's a valid URL and not from admin
								//add the url in the WP rewrite list
								$mappings = (array) HMWP_Classes_Tools::getOption( 'file_mappings' );
								if ( count( $mappings ) < 10 ) {
									$mappings[ md5( $url ) ] = $url;
									HMWP_Classes_Tools::saveOptions( 'file_mappings', $mappings );
								}

								//for debug
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

					// Only the CSS, JS and SCSS content is changed before it's sent
					$ismapped = ( strpos( $new_url, '.js' ) || strpos( $new_url, '.css' ) || strpos( $new_url, '.scss' ) );

					// Build the validator before the file is read
					$validator = $this->getFileValidator( $new_path, $ismapped );

					header( "Cache-Control: max-age=2592000, must-revalidate" );
					header( "Expires: " . gmdate( 'r', strtotime( "+1 month" ) ) );
					header( "Vary: Accept-Encoding" );
					header( "Pragma: public" );
					header( 'Etag: "' . $validator['etag'] . '"' );

					if ( $validator['mtime'] > 0 ) {
						header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $validator['mtime'] ) . ' GMT' );
					}

					// Answer the conditional request without reading the file
					if ( $this->isNotModified( $validator['etag'], $validator['mtime'] ) ) {

						$this->closeBuffers();

						if ( function_exists( 'http_response_code' ) ) {
							http_response_code( 304 );
						}

						header( "HTTP/1.1 304 Not Modified" );
						exit();
					}

					if ( function_exists( 'http_response_code' ) ) {
						http_response_code( 200 );
					}

					header( "HTTP/1.1 200 OK" );

					if ( $mime ) {
						header( 'Content-Type: ' . $mime . '; charset: UTF-8' );
					}

					//////////////////////////////////////////////////////////////////////////
					// The images, fonts and media need no change, send them without loading them in memory
					if ( ! $ismapped ) {

						$this->closeBuffers();

						if ( $validator['size'] > 0 ) {
							header( 'Content-Length: ' . $validator['size'] );
						}

						if ( readfile( $new_path ) === false ) {
							echo $wp_filesystem->get_contents( $new_path ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}

						exit();
					}

					//////////////////////////////////////////////////////////////////////////
					// If CSS, JS or SCSS
					$encoding = $this->getAcceptedEncoding();

					if ( $encoding ) {
						header( 'Content-Encoding: ' . $encoding );
					}

					// The changed content is the same for every visitor, build it once
					$cachefile = ( $this->isCacheable() ? $this->getCacheFile( $validator['etag'], $encoding ) : '' );

					if ( $cachefile && $wp_filesystem->exists( $cachefile ) ) {

						$this->closeBuffers();

						if ( $cachesize = (int) $wp_filesystem->size( $cachefile ) ) {
							header( 'Content-Length: ' . $cachesize );
						}

						if ( readfile( $cachefile ) !== false ) {
							exit();
						}
					}

					ob_clean(); //clear the buffer
					$content = $wp_filesystem->get_contents( $new_path );

					// URL Mapping for all css and js files
					$content = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->find_replace_url( $content );
					// Text Mapping for all css and js files
					$content = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->replaceTextMapping( $content, true );

					// Compress the CSS and JS only with what the browser accepts
					switch ( $encoding ) {
						case 'br':
							$content = brotli_compress( $content, 1 );
							break;
						case 'gzip':
							$content = gzencode( $content );
							break;
					}

					// Keep the built content for the next request
					if ( $cachefile ) {
						$this->saveCacheFile( $cachefile, $content );
					}

					// Show the file html content
					header( 'Content-Length: ' . strlen( $content ) );

					echo $content; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
						echo $response['body']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

			} elseif ( HMWP_Classes_Tools::isMultisites() && stripos( trailingslashit( $url_no_query ), '/' . HMWP_Classes_Tools::getOption( 'hmwp_activate_url' ) . '/' ) !== false ) {

				if ( $new_path && strpos( $new_path, 'wp-activate.php' ) && $wp_filesystem->exists( $new_path ) ) {
					header( "HTTP/1.1 200 OK" );

					ob_start();
					global $wp_object_cache, $wp_query;
					require_once $new_path;
					$content = ob_get_clean();

					//Echo the html file content
					echo $content; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

		// Only requests to this site are allowed
		if ( ! $this->isSiteUrl( $url ) ) {
			return array();
		}

		$headers = $this->getForwardHeaders();
		$options = array(
			'method'    => 'POST',
			'headers'   => $headers,
			'body'      => $_POST, //phpcs:ignore WordPress.Security.NonceVerification.Missing
			'timeout'   => 30,
			'sslverify' => false,
		);

		do_action( 'hmwp_files_post_request_before', $url, $options );

		$response = wp_remote_post( $url, $options );

		$return['body']    = wp_remote_retrieve_body( $response );
		$return['headers'] = $this->getResponseHeaders( $response );

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

		// Only requests to this site are allowed
		if ( ! $this->isSiteUrl( $url ) ) {
			return array();
		}

		$headers = $this->getForwardHeaders();
		$options = array(
			'method'    => 'GET',
			'headers'   => $headers,
			'timeout'   => 30,
			'sslverify' => false,
		);

		do_action( 'hmwp_files_get_request_before', $url, $options );

		$response = wp_remote_get( $url, $options );

		$return['body']    = wp_remote_retrieve_body( $response );
		$return['headers'] = $this->getResponseHeaders( $response );

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
	 */
	public function handleLogin( $url ) {
		$url = rawurldecode( $url );

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

			$urled_redirect_to = HMWP_Classes_Tools::getValue( 'redirect_to', '' );

			//if user is logged in
			if ( HMWP_Classes_Tools::isLoggedInUser() ) {
				if ( HMWP_Classes_Tools::getOption( 'hmwp_logged_users_redirect' ) ) {
					/** @var HMWP_Models_Rewrite $rewriteModel */
					$rewriteModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' );
					$rewriteModel->dashboard_redirect();
				}
			}

			global $wp_query, $error, $interim_login, $action, $user_login;

			$wp_query->is_404 = false;
			if ( ! empty( $error ) ) {
				$error = false;
			}

			require_once ABSPATH . 'wp-login.php';
			die();

		} elseif ( HMWP_Classes_Tools::getOption( 'hmwp_logout_url' ) <> '' && strpos( $url, '/' . HMWP_Classes_Tools::getOption( 'hmwp_logout_url' ) ) ) {

			check_admin_referer( 'log-out' );

			do_action( 'hmwp_files_handle_logout', $url );

			$user = wp_get_current_user();

			wp_logout();

			$redirect_to = $requested_redirect_to = HMWP_Classes_Tools::getValue( 'redirect_to' );

			if ( ! $redirect_to ) {
				$redirect_to           = add_query_arg( array(
					'loggedout' => 'true',
					'wp_lang'   => get_user_locale( $user ),
				), wp_login_url() );
				$requested_redirect_to = '';
			}

			$redirect_to = apply_filters( 'logout_redirect', $redirect_to, $requested_redirect_to, $user );

			wp_safe_redirect( $redirect_to );
			exit;
		}

	}

}
