<?php
/**
 * Compatibility Class
 *
 * @file The Abstract Model file
 * @package HMWP/Compatibility/Abstract
 * @since 7.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

abstract class HMWP_Models_Bruteforce_Abstract {

	/**
	 * Show the header for the selected Brute Force
	 *
	 * @return void outputs html
	 */
	public function head() {}

	/**
	 * Show the form for the selected Brute Force
	 *
	 * @return void outputs html
	 */
	public function form() {}

	/**
	 * Checks for pre authentication BEFORE authentication so that bots don't get to go around the login form.
	 * If we are using our math fallback, authenticate via math-fallback.php
	 *
	 * @param  string  $user  Passed via WordPress action. Not used.
	 *
	 * @return bool True, if WP_Error. False, if not WP_Error., $user Containing the auth results
	 * @throws Exception
	 */
	function pre_authentication( $user = '' ) {

		if ( ! apply_filters( 'hmwp_preauth_check', true ) ) {
			return $user;
		}

		/** @var HMWP_Models_Brute $bruteForceModel */
		$bruteForceModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' );

		// Check Brute Force Math or Google reCaptcha
		$response = $bruteForceModel->bruteForceCheck();

		// If this is a whitelist IP, return
		if ( $response['status'] == 'whitelist' ) {
			return $user;
		}

		// Check the error in authentication
		if ( is_wp_error( $user ) ) {
			if ( method_exists( $user, 'get_error_codes' ) ) {
				$errors = $user->get_error_codes();

				if ( ! empty( $errors ) ) {
					foreach ( $errors as $error ) {

						// Don't process the attempts if the fields are empty
						if ( $error == 'empty_username' || $error == 'empty_password' ) {
							return $user;
						}

						// Check if the brute force username option is enabled
						if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_username' ) ) {
							if ( $error == 'invalid_username' ) {

								// Get current IP
								/** @var HMWP_Models_Bruteforce_IpAddress $bruteForceIp */
								$bruteForceIp = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_IpAddress' );

								// Block current IP on invalid username
								$bruteForceModel->blockIp( $bruteForceIp->getIp() );

								// Stop the process here
								$bruteForceModel->bruteForceBlock();
							}
						}
					}
				}
			}
		}

		// Check the reCaptcha error
		$user = $this->authenticate( $user, $response );

		// If there is a login error
		if ( is_wp_error( $user ) ) {

			// Show the number of attempts left based on the failed attempts
			if ( isset( $response['attempts'] ) ) {

				//show how many attempts remained
				$attempts_left = max(((int)HMWP_Classes_Tools::getOption('brute_max_attempts') - $response['attempts']), 1);

				$user = new WP_Error( 'authentication_failed', $user->get_error_message() . '<br />' . sprintf( esc_html__( 'You got %d attempts left before lockout.', 'hide-my-wp' ), $attempts_left ) );
			}

		}

		// If the login went successfully
		if ( ! is_wp_error( $user ) ) {
			// remove the failed attempts for this IP
			$this->success();
		}

		return $user;
	}

	/**
	 * Called when success login is triggered
	 *
	 * @return void
	 * @throws Exception
	 */
	function success() {
		/** @var HMWP_Models_Brute $bruteForceModel */
		$bruteForceModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' );

		// Register the process as failed
		$bruteForceModel->processIp( 'clear_ip' );
	}

	/**
	 * Called via WP action wp_login_failed to log failed attempt in db
	 *
	 * @return void
	 * @throws Exception
	 */
	function failed() {
		/** @var HMWP_Models_Brute $bruteForceModel */
		$bruteForceModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' );

		// Register the process as failed
		$bruteForceModel->processIp( 'failed_attempt' );
	}

}
