<?php
/**
 * Brute Force Protection
 * Called when the Brute Force Protection is activated
 *
 * @file  The Brute Force file
 * @package HMWP/BruteForce
 * @since 4.2.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

/**
 * Class HMWP_Controllers_Brute
 *
 * Handles brute force protection mechanisms including login, registration, and lost password
 * attempts. Integrates various captcha methods to safeguard against automated attacks.
 */
class HMWP_Controllers_Brute extends HMWP_Classes_FrontController {

	/**
	 * Constructor method for initializing the class.
	 *
	 * Registers default options and ensures that specific settings, such as the brute message option, are properly initialized.
	 * Also sets up necessary hooks for the class functionality.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function __construct() {

		// Call parent constructor
		parent::__construct();

		// Load all Brute Force instances
		$this->init();
	}

	/**
	 * Load all Brute Force instances
	 *
	 * @throws Exception
	 */
	public function init() {

		// If the safe parameter is set, clear the banned IPs and let the default paths
		if ( ! $this->doBruteForce() ) {
			return;
		}

		// Load Brute Force for shortcodes
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Shortcode' );

		// Check Brute Force on login
		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_login' ) ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Login' );

			// Extend the same login brute force protection to the REST API
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_RestApi' );
		}
		// Check Brute Force on a lost password
		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_lostpassword' ) ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_LostPassword' );
		}
		//Check Brute Force on comments
		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_comments' ) ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Comments' );
		}
		//Check Brute Force on register
		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_register' ) ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Registration' );
		}

		// Check brute force
		$this->model->bruteForceCheck();
	}

	/**
	 * Checks conditions for triggering Brute Force functionalities.
	 *
	 * This method determines whether a Brute Force mechanism should be initiated
	 * based on the current state, such as whether a safe URL is called or if
	 * the user is logged in and accessing the admin area.
	 *
	 * @return bool Returns true if Brute Force actions should be executed, false otherwise.
	 * @throws Exception
	 */
	public function doBruteForce() {

		// If safe URL is called
		if ( HMWP_Classes_Tools::calledSafeUrl() ) {
			return false;
		}

		//If not admin but logged in
		if ( ! is_admin() && ! is_network_admin() ) {

			//if a user is not logged in
			if ( ! HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cookies' )->isLoggedInCookie() ) {
				return true;
			}

		}

		return false;
	}

	/**
	 * Handles various actions related to brute force protection and IP management.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function action() {
		// Call parent action
		parent::action();

		// Check if the current user has the 'hmwp_manage_settings' capability
		if ( ! HMWP_Classes_Tools::userCan( HMWP_CAPABILITY ) ) {
			return;
		}

		// Handle different actions
		switch ( HMWP_Classes_Tools::getValue( 'action' ) ) {

			case 'hmwp_brutesettings':
				// Save the brute force-related settings
				if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveValues( $_POST ); //phpcs:ignore
				}

				// Brute force math option
				if ( HMWP_Classes_Tools::getValue( 'hmwp_bruteforce' ) ) {
					$attempts = (int) HMWP_Classes_Tools::getValue( 'brute_max_attempts' );
					if ( $attempts <= 0 ) {
						$attempts = 3;
						HMWP_Classes_Error::setNotification( esc_html__( 'You need to set a positive number of attempts.', 'hide-my-wp' ) );
					}
					HMWP_Classes_Tools::saveOptions( 'brute_max_attempts', $attempts );

					$timeout = (int) HMWP_Classes_Tools::getValue( 'brute_max_timeout' );
					if ( $timeout <= 0 ) {
						$timeout = 3600;
						HMWP_Classes_Error::setNotification( esc_html__( 'You need to set a positive waiting time.', 'hide-my-wp' ) );

					}
					HMWP_Classes_Tools::saveOptions( 'brute_max_timeout', $timeout );
				}

				// Save the text every time to prevent from removing the white space from the text
				HMWP_Classes_Tools::saveOptions( 'hmwp_brute_message', HMWP_Classes_Tools::getValue( 'hmwp_brute_message', '', true ) );

				// Clear the cache if there are no errors
				if ( ! HMWP_Classes_Tools::getOption( 'error' ) ) {

					if ( ! HMWP_Classes_Tools::getOption( 'logout' ) ) {
						HMWP_Classes_Tools::saveOptionsBackup();
					}

					HMWP_Classes_Error::setNotification( esc_html__( 'Saved', 'hide-my-wp' ), 'success' );
				}

				break;

			case 'hmwp_google_enterprise':

				// Switch between google classic and google enterprise
				HMWP_Classes_Tools::saveOptions( 'brute_use_google_enterprise', HMWP_Classes_Tools::getValue( 'brute_use_google_enterprise' ) );

				break;
			case 'hmwp_deleteip':
				// Delete a specific IP from the blocked list
				$ip = HMWP_Classes_Tools::getValue( 'ip' );
				if ( $ip ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->delete( $ip );
				}

				break;
			case 'hmwp_deleteallips':
				// Clear all blocked IPs
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->clearBlockedIPs();

				break;


		}
	}

}
