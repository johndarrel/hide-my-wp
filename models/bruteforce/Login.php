<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force LostPassword file
 * @package HMWP/BruteForce/LostPassword
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Bruteforce_Login {

	public function __construct() {

		// Get the active brute force class
		$bruteforce = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' )->getInstance();

		// Listen to the login process and check for brute force attempts
		add_filter( 'authenticate', array( $bruteforce, 'pre_authentication' ), 99, 1 );

		add_action( 'wp_login_failed', array( $bruteforce, 'failed' ), 99 );
		add_action( 'login_head', array( $bruteforce, 'head' ), 99 );
		add_action( 'login_form', array( $bruteforce, 'form' ), 99 );

	}


}
