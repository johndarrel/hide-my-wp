<?php
/**
 * Handles the parameters and url
 *
 * @package HMWP/Debug
 * @file The Debug file
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Classes_Debug {

	/**
	 * Constructor for initializing WordPress filesystem and setting up debug directories and hooks.
	 *
	 * @return void
	 */
	public function __construct() {

		// Initialize WordPress Filesystem.
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if ( defined( 'WP_CONTENT_DIR' ) ) {

			// If debug dir doesn't exists.
			if ( ! $wp_filesystem->is_dir( WP_CONTENT_DIR . '/cache/hmwp' ) ) {
				$wp_filesystem->mkdir( WP_CONTENT_DIR . '/cache/hmwp' );
			}

			// If the debug dir can't be defined.
			if ( ! $wp_filesystem->is_dir( WP_CONTENT_DIR . '/cache/hmwp' ) ) {
				return;
			}

			define( '_HMWP_CACHE_DIR_', WP_CONTENT_DIR . '/cache/hmwp/' );

			//Initialize debugging hooks for later use
			add_action( 'hmwp_debug_request', array( $this, 'hookDebugRequests' ) );
			add_action( 'hmwp_debug_cache', array( $this, 'hookDebugCache' ) );
			add_action( 'hmwp_debug_files', array( $this, 'hookDebugFiles' ) );
			add_action( 'hmwp_debug_local_request', array( $this, 'hookDebugRequests' ) );
			add_action( 'hmwp_debug_access_log', array( $this, 'hookAccessLog' ) );
		}

	}


	/**
	 * Hook to log debug requests.
	 *
	 * @param  string  $url  The URL of the request being logged.
	 * @param  array  $options  The options for the request.
	 * @param  array  $response  The response received from the request.
	 *
	 * @return void
	 */
	public function hookDebugRequests( $url, $options = array(), $response = array() ) {

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		$wp_filesystem->put_contents( _HMWP_CACHE_DIR_ . 'hmwp_wpcall.log', date( 'Y-m-d H:i:s' ) . ' - ' . $url . ' - ' . json_encode( $response ) . PHP_EOL, FILE_APPEND );

	}

	/**
	 * Logs debugging information to the cache directory.
	 *
	 * @param  string  $data  The data to be logged.
	 *
	 * @return void
	 */
	public function hookDebugCache( $data ) {

		// Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		$wp_filesystem->put_contents( _HMWP_CACHE_DIR_ . 'rewrite.log', $data );

	}

	/**
	 * Hook to log debug information into filecall.log within the HMWP cache directory.
	 *
	 * @param  string  $data  The debug data to be logged.
	 *
	 * @return void
	 */
	public function hookDebugFiles( $data ) {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		$wp_filesystem->put_contents( _HMWP_CACHE_DIR_ . 'filecall.log', $data . PHP_EOL, FILE_APPEND );

	}

	/**
	 * Hooks into the access log by writing data to a log file within the WordPress filesystem.
	 *
	 * @param  string  $data  The data to be logged.
	 *
	 * @return void
	 */
	public function hookAccessLog( $data ) {

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		$wp_filesystem->put_contents( _HMWP_CACHE_DIR_ . 'access.log', $data . PHP_EOL, FILE_APPEND );

	}

}
