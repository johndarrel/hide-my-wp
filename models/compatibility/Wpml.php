<?php
/**
 * Compatibility Class
 *
 * @file The WPML Model file
 * @package HMWP/Compatibility/WPML
 * @since 7.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility_Wpml extends HMWP_Models_Compatibility_Abstract {

	public function __construct() {
		parent::__construct();

		//WPML checks the HTTP_REFERER based on wp-admin and not the custom admin path
		if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
			if ( HMWP_Classes_Tools::getDefault( 'hmwp_admin_url' ) <> HMWP_Classes_Tools::getOption( 'hmwp_admin_url' ) ) {
				$_SERVER['HTTP_REFERER'] = esc_url(HMWP_Classes_ObjController::getClass( 'HMWP_Models_Files' )->getOriginalUrl( wp_unslash( $_SERVER['HTTP_REFERER']) )); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			}
		}

		// WPML Advanced Translation Editor (ATE) jobs synchronization.
		//
		// The static ate-jobs-sync/app.js polls the WPML REST routes
		// /wp-json/wpml/tm/v1/ate/jobs/sync (and /retry) and admin-ajax.
		// Leave the WPML translation/ATE requests untouched.
		$uri = false;
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$uri = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		if (
			$uri &&
			(
				strpos( $uri, '/' . HMWP_Classes_Tools::getOption( 'hmwp_wp-json' ) . '/wpml/' ) !== false ||
				strpos( $uri, '/wp-json/wpml/' ) !== false ||
				strpos( $uri, '/wpml/tm/' ) !== false ||
				strpos( $uri, '/ate/jobs/' ) !== false
			)
		) {
			add_filter( 'hmwp_process_hide_urls', '__return_false' );
			add_filter( 'hmwp_process_firewall', '__return_false' );
			add_filter( 'hmwp_process_threats', '__return_false' );
			add_filter( 'hmwp_process_find_replace', '__return_false' );
			add_filter( 'hmwp_process_buffer', '__return_false' );
		}
	}

	/**
	 * WPML / ICL admin-ajax requests (including the ATE jobs-sync triggers).
	 *
	 * Skip the find/replace + buffer rewrite so the WPML language prefix
	 * (e.g. /de/) isn't injected into the URLs returned in the AJAX payload,
	 * which would make the static WPML app.js fetch a broken URL.
	 *
	 * @return void
	 */
	public function hookAjax() {

		$action = HMWP_Classes_Tools::getValue( 'action' );

		if ( $action == '' ) {
			// Legacy WPML custom-call dispatcher uses its own param
			$action = HMWP_Classes_Tools::getValue( 'icl_ajx_action' );
		}

		if ( $action <> '' && (
			strpos( $action, 'wpml' ) === 0 ||
			strpos( $action, 'icl' ) === 0
		) ) {
			add_filter( 'hmwp_process_find_replace', '__return_false' );
			add_filter( 'hmwp_process_buffer', '__return_false' );
		}

	}

}
