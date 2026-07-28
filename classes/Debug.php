<?php
/**
 * Handles the parameters and url
 *
 * @package HMWP/Debug
 * @file The Debug file
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

/**
 * Class HMWP_Classes_Debug
 *
 * Handles debugging operations for plugin. Hooks into various debug actions
 * to log specific details such as requests, cache data, files, and access logs.
 */
class HMWP_Classes_Debug {

	/**
	 * Constructor. Registers debug action hooks when both WP_DEBUG and HMWP_DEBUG are enabled.
	 *
	 * Hooks are only attached when the plugin is running in full debug mode so there
	 * is no overhead in production environments.
	 */
	public function __construct() {

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'HMWP_DEBUG' ) && HMWP_DEBUG ) {
			add_action( 'hmwp_debug_request', array( $this, 'hookDebugRequests' ), 11, 3 );
			add_action( 'hmwp_debug_local_request', array( $this, 'hookDebugRequests' ), 11, 3 );
			add_action( 'hmwp_debug_cache', array( $this, 'hookDebugCache' ) );
			add_action( 'hmwp_debug_files', array( $this, 'hookDebugFiles' ) );
			add_action( 'hmwp_debug_access_log', array( $this, 'hookAccessLog' ) );
		}

	}

	/**
	 * @param string $url The URL of the request.
	 * @param array $options An array of options associated with the request.
	 * @param array $response An array containing the response data.
	 *
	 * @return void
	 */
	public function hookDebugRequests( $url, $options = array(), $response = array() ) {
		error_log( 'HMWP Request: ' . $url . PHP_EOL . print_r( $options, true )  . PHP_EOL . print_r( $response, true )); //phpcs:ignore
	}

	/**
	 * @param  string  $data
	 *
	 * @return void
	 */
	public function hookDebugCache( $data ) {
		error_log( 'HMWP Cache: ' . $data ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * @param  string  $data
	 *
	 * @return void
	 */
	public function hookDebugFiles( $data ) {
		error_log( 'HMWP File: ' . $data ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	/**
	 * @param  string  $data
	 *
	 * @return void
	 */
	public function hookAccessLog( $data ) {
		error_log( 'HMWP Acess: ' . $data ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

}
