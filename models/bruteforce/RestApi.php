<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force RestApi file
 * @package HMWP/BruteForce/RestApi
 * @since 7.0.3
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Bruteforce_RestApi {

	public function __construct() {

		// Get the active brute force class
		/** @var HMWP_Models_Brute $bruteforce */
		$bruteforce = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' )->getInstance();

		// WordPress Application Password authentication runs on
		// determine_current_user, so failed attempts never fire the
		// 'authenticate' filter or 'wp_login_failed' action that the login
		// form brute force listens to. Without this an attacker can brute
		// force credentials through Authorization: Basic on any /wp-json/
		// endpoint, bypassing the login-page protection entirely.
		add_action( 'application_password_failed_authentication', array( $bruteforce, 'failed' ), 99 );

	}


}
