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

class HMWP_Models_Bruteforce_LostPassword {

	public function __construct() {

		// Get the active brute force class
		$bruteforce = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' )->getInstance();

		add_filter( 'lostpassword_errors', array( $this, 'call' ), 99, 2 );
		add_filter( 'lostpassword_form', array( $bruteforce, 'form' ), 99 );

	}

	/**
	 * Checks the form BEFORE registration so that bots don't get to go around the register form.
	 *
	 * @param $errors
	 * @param $sanitizedLogin
	 * @param $userEmail
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function call( $errors, $user ) {

		// Check bruteforce only in frontend for not logged users
		if ( ! function_exists( 'is_user_logged_in' ) || is_user_logged_in() ) {
			return $errors;
		}

		/** @var HMWP_Models_Brute $bruteForceModel */
		$bruteForceModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' );

		// Check bruteforce only in frontend for not logged users
		$response = $bruteForceModel->bruteForceCheck();

		// Get the active Brute Force class
		$error = $bruteForceModel->getInstance()->authenticate( false, $response );

		if ( is_wp_error( $error ) ) {

			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( $error->get_error_message(), 'error' );
				add_filter( 'allow_password_reset', '__return_false' );
			}

			return $error;
		}

		return $errors;
	}

}
