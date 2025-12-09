<?php
/**
 * Compatibility Class
 *
 * @file The LiteSpeed Model file
 * @package HMWP/Compatibility/LiteSpeed
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Compatibility_LiteSpeed extends HMWP_Models_Compatibility_Abstract {

	public function __construct() {

		parent::__construct();

		//Set custom cache path for litespeed
		defined( 'LITESPEED_DATA_FOLDER' ) || define( 'LITESPEED_DATA_FOLDER', 'cache/ls' );

		//Check and handle LiteSpeed media optimization scan.
		add_filter( 'hmwp_process_init', array( $this, 'checkLiteSpeedScan' ) );
		add_filter( 'hmwp_process_buffer', array( $this, 'checkLiteSpeedScan' ) );
		add_filter( 'hmwp_process_hide_urls', array( $this, 'checkLiteSpeedScan' ) );
		add_filter( 'hmwp_process_find_replace', array( $this, 'checkLiteSpeedScan' ) );

		// Whitelist Litespeed quic cloud Ips after settings save
		add_action( 'hmwp_settings_saved', function (){
			$quic_ips = HMWP_Classes_Tools::hmwp_remote_get( 'https://www.quic.cloud/ips-all?json' );
			set_transient( 'hmwp_lispeed_ips', $quic_ips, WEEK_IN_SECONDS );
		} );

		// Add Litespeed IPs in whitelist
		if( $quic_ips = get_transient('hmwp_lispeed_ips') ){
			add_filter( 'hmwp_whitelisted_ips', function ( $ips ) use ( $quic_ips ) {

				if ( !empty($quic_ips) && $quic_ips = json_decode( $quic_ips, true ) ){

					$quic_ips = array_filter(array_map( function ( $ip ){
						if ( !filter_var( $ip, FILTER_VALIDATE_IP ) ) {
							return false;
						}
						return trim($ip);
					}, $quic_ips ));

					if( !empty( $quic_ips ) ){
						return array_merge( $ips, $quic_ips );
					}
				}

				return $ips;

			});

			/** @var HMWP_Controllers_Firewall $firewall */
			HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Firewall' )->checkWhitelistIPs();
		}
	}

	/**
	 * Check and handle LiteSpeed media optimization scan.
	 *
	 * @param  bool  $status  The current status of the scan.
	 *
	 * @return bool The updated status of the scan.
	 */
	public function checkLiteSpeedScan( $status ) {

		// Check if the scan is starting manually via cron or AJAX
		if ( HMWP_Classes_Tools::isCron() || HMWP_Classes_Tools::isAjax() ) {

			// Check if the action is Wordfence scan
			if ( 'async_litespeed' == HMWP_Classes_Tools::getValue( 'action' ) ) {
				$status = false;
			}
		}

		return $status;
	}


	public function hookAdmin() {
		add_action( 'wp_initialize_site', function ( $site_id ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();
		}, PHP_INT_MAX, 1 );

		add_action( 'create_term', function ( $term_id ) {
			add_action( 'admin_footer', function () {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();
			} );
		}, PHP_INT_MAX, 1 );

		// Wait for the cache on litespeed servers and flush the changes
		add_action( 'hmwp_apply_permalink_changes', function () {
			add_action( 'admin_footer', function () {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();
			} );
		} );

		// Only if the litespeed plugin is installed
		if ( HMWP_Classes_Tools::isPluginActive( 'litespeed-cache/litespeed-cache.php' ) ) {

			if ( ! HMWP_Classes_Tools::isWpengine() ) {
				add_action( 'hmwp_settings_saved', array( $this, 'doExclude' ) );
			}
		}

	}

	public function hookFrontend() {

		// Don't load plugin buffer if litespeed
		add_action( 'litespeed_initing', function () {
			if ( ! defined( 'LITESPEED_DISABLE_ALL' ) || ! defined( 'LITESPEED_GUEST_OPTM' ) ) {
				add_filter( 'hmwp_process_buffer', '__return_false' );
			}
		} );

		// Change the path withing litespeed buffer
		add_filter( 'litespeed_buffer_after', array( $this, 'findReplaceCache' ), PHP_INT_MAX );

		// Set priority load for compatibility
		add_filter( 'litespeed_comment', '__return_false' );

	}

	/**
	 * Excludes specific login URLs from LiteSpeed caching configuration based on
	 * the current and default hidden login URLs set by the plugin.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function doExclude() {

		if ( HMWP_Classes_Tools::getDefault( 'hmwp_login_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_login_url' ) ) {

			$exlude = get_option( 'litespeed.conf.cache-exc' );

			// If there are already URLs in the exclude list
			if ( $exlude = json_decode( $exlude, true ) ) {
				// Add custom login in caching exclude list
				if ( ! in_array( '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' ), $exlude ) ) {
					$exlude[] = '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' );
				}
			} else {
				$exlude   = array();
				$exlude[] = '/' . HMWP_Classes_Tools::getOption( 'hmwp_login_url' );
			}

			update_option( 'litespeed.conf.cache-exc', wp_json_encode( $exlude ) );
		}

		if ( HMWP_Classes_Tools::getDefault( 'hmwp_wp-json' ) <> HMWP_Classes_Tools::getOption( 'hmwp_wp-json' ) ) {

			$exlude = get_option( 'litespeed.conf.cache-exc' );

			// If there are already URLs in the exclude list
			if ( $exlude = json_decode( $exlude, true ) ) {
				// Add REST API in caching exclude list
				if ( ! in_array( '/' . HMWP_Classes_Tools::getOption( 'hmwp_wp-json' ), $exlude ) ) {
					$exlude[] = '/' . HMWP_Classes_Tools::getOption( 'hmwp_wp-json' );
				}

			} else {
				$exlude   = array();
				$exlude[] = '/' . HMWP_Classes_Tools::getOption( 'hmwp_wp-json' );
			}

			update_option( 'litespeed.conf.cache-exc', wp_json_encode( $exlude ) );
		}

	}
}
