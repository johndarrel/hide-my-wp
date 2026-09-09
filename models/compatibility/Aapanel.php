<?php
/**
 * aaPanel Class
 *
 * @file The aaPanel Model file
 * @package HMWP/Compatibility/Aapanel
 * @since 9.1.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

/**
 * Compatibility with the aaPanel WP Toolkit one click login
 *
 * The key and token pair is verified here before anything is relaxed, and hiding is
 * never switched off. The toolkit fires wp_login, so two factor is raised as usual.
 */
class HMWP_Models_Compatibility_Aapanel extends HMWP_Models_Compatibility_Abstract {

	//The options the aaPanel WP Toolkit stores its credentials in
	const OPTION_KEY   = 'aapanel_WPToolkitSecurityKey';
	const OPTION_TOKEN = 'aapanel_WPToolkitSecurityToken';

	//The parameter that carries the toolkit action, and the login action itself
	const PARAM_ACTION = '_aap_action';
	const LOGIN_ACTION = 'auto_login';

	public function __construct() {
		parent::__construct();

		$this->hookPanelLogin();
	}

	/**
	 * Let a verified panel login reach WordPress
	 *
	 * The token is a long random value the firewall can read as an attack pattern.
	 *
	 * @return void
	 */
	public function hookPanelLogin() {

		if ( HMWP_Classes_Tools::getValue( self::PARAM_ACTION ) <> self::LOGIN_ACTION ) {
			return;
		}

		if ( ! $this->isValidSecurityPair() ) {
			return;
		}

		add_filter( 'hmwp_process_firewall', '__return_false' );
		add_filter( 'hmwp_process_threats', '__return_false' );
	}

	/**
	 * Verify the security key and token against the pair aaPanel stored
	 *
	 * Sent either as a request header or in the query string, keyed by the security key.
	 *
	 * @return bool
	 */
	private function isValidSecurityPair() {

		$key   = get_option( self::OPTION_KEY );
		$token = get_option( self::OPTION_TOKEN );

		if ( ! is_string( $key ) || $key == '' || ! is_string( $token ) || $token == '' ) {
			return false;
		}

		$header = 'HTTP_AAP_WP_TOOLKIT_' . strtoupper( $key );

		if ( isset( $_SERVER[ $header ] ) ) {
			if ( hash_equals( $token, (string) wp_unslash( $_SERVER[ $header ] ) ) ) { //phpcs:ignore
				return true;
			}
		}

		if ( isset( $_GET[ $key ] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			if ( hash_equals( $token, (string) wp_unslash( $_GET[ $key ] ) ) ) { //phpcs:ignore
				return true;
			}
		}

		return false;
	}

}
