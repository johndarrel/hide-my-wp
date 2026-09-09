<?php
/**
 * Softaculous Class
 *
 * @file The Softaculous Model file
 * @package HMWP/Compatibility/Softaculous
 * @since 9.1.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

/**
 * Compatibility with the Softaculous WordPress Manager auto login
 *
 * Used by cPanel, CentOS Web Panel, DirectAdmin, Webuzo, InterWorx and Plesk. The sign
 * on key is verified here before anything is relaxed, and hiding is never switched off.
 */
class HMWP_Models_Compatibility_Softaculous extends HMWP_Models_Compatibility_Abstract {

	//The ajax action Softaculous calls to log the administrator in
	const LOGIN_ACTION = 'wpcentral_login_and_act';

	//The sign on key expires 5 minutes after it is created, as in the Softaculous plugin
	const KEY_LIFETIME = 300;

	public function __construct() {
		parent::__construct();

		$this->hookPanelLogin();
	}

	/**
	 * Let a verified panel sign on reach WordPress
	 *
	 * The key is a long random value the firewall can read as an attack pattern.
	 *
	 * @return void
	 */
	public function hookPanelLogin() {

		if ( HMWP_Classes_Tools::getValue( 'action' ) <> self::LOGIN_ACTION ) {
			return;
		}

		if ( ! $this->isValidSignonKey() ) {
			return;
		}

		add_filter( 'hmwp_process_firewall', '__return_false' );
		add_filter( 'hmwp_process_threats', '__return_false' );

		//Softaculous never fires wp_login, so raise the two factor challenge here
		if ( HMWP_Classes_Tools::getOption( 'hmwp_2falogin' ) ) {
			add_action( 'set_auth_cookie', array( $this, 'hookTwoFactor' ), 99, 4 );
		}
	}

	/**
	 * Ask for the second factor on a panel login
	 *
	 * Runs before WordPress sends the cookies, so no session is handed out until answered.
	 *
	 * @param  string  $auth_cookie  The authentication cookie
	 * @param  int  $expire  The time the cookie expires
	 * @param  int  $expiration  The time the authentication expires
	 * @param  int  $user_id  The logged in user id
	 *
	 * @return void
	 * @throws Exception
	 */
	public function hookTwoFactor( $auth_cookie, $expire, $expiration, $user_id ) {

		if ( ! $user = get_userdata( $user_id ) ) {
			return;
		}

		/** @var HMWP_Controllers_Twofactor $twofactor */
		$twofactor = HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Twofactor' );

		//hookLogin() returns on its own when no service is active for this user
		$twofactor->hookLogin( $user->user_login, $user );
	}

	/**
	 * Verify the one time sign on key against the key Softaculous stored
	 *
	 * Only read here, Softaculous deletes it when it uses it.
	 *
	 * @return bool
	 */
	private function isValidSignonKey() {

		//Read the raw value so it compares exactly as Softaculous wrote it
		$key = false;

		if ( isset( $_POST['softaculous_signonkey'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			$key = wp_unslash( $_POST['softaculous_signonkey'] ); //phpcs:ignore
		} elseif ( isset( $_GET['softaculous_signonkey'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
			$key = wp_unslash( $_GET['softaculous_signonkey'] ); //phpcs:ignore
		}

		if ( ! is_string( $key ) || $key == '' ) {
			return false;
		}

		$stored = get_option( 'softaculous_signonkey' );

		if ( ! is_string( $stored ) || $stored == '' ) {
			return false;
		}

		if ( ! hash_equals( $stored, $key ) ) {
			return false;
		}

		$created = (int) get_option( 'softaculous_signonkey_time' );

		return ( $created > 0 && ( time() - $created ) <= self::KEY_LIFETIME );
	}

}
