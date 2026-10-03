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

		// A logged-in cookie can't be verified before pluggable.php loads,
		// so run again at plugins_loaded where the signature is checked
		if ( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cookies' )->deferUntilVerified( array( $this, 'init' ) ) ) {
			return;
		}

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
			if ( ! HMWP_Classes_ObjController::getClass( 'HMWP_Models_Cookies' )->isLoggedInRequest() ) {
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
			case 'hmwp_recaptcha_check':
				// Verify the reCAPTCHA keys typed in the settings form
				$this->checkRecaptcha();

				break;


		}
	}

	/**
	 * Verify the reCAPTCHA keys from the settings form with a token generated on the admin page
	 *
	 * Answers the AJAX request with a message and, on success, the score, action and hostname Google reported.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function checkRecaptcha() {
		$type  = HMWP_Classes_Tools::getValue( 'type' );
		$token = HMWP_Classes_Tools::getValue( 'token' );

		if ( ! in_array( $type, array( 'v2', 'v3', 'enterprise' ), true ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unknown reCAPTCHA type.', 'hide-my-wp' ) ) );
		}

		if ( $token == '' ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Google did not return a token. Check the Site Key and the domains it is registered for.', 'hide-my-wp' ) ) );
		}

		$details = array();

		if ( $type == 'enterprise' ) {
			$site_key   = HMWP_Classes_Tools::getValue( 'site_key' );
			$project_id = HMWP_Classes_Tools::getValue( 'project_id' );
			$api_key    = HMWP_Classes_Tools::getValue( 'api_key' );

			if ( $site_key == '' || $project_id == '' || $api_key == '' ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Fill in the Site Key, the Project ID and the API Key first.', 'hide-my-wp' ) ) );
			}

			/** @var HMWP_Models_Bruteforce_Google $service */
			$service  = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Google' );
			$response = $service->assess( $project_id, $api_key, $site_key, $token );

			if ( empty( $response ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Google could not be reached from this server.', 'hide-my-wp' ) ) );
			}

			if ( isset( $response['error'] ) ) {
				$status  = isset( $response['error']['status'] ) ? (string) $response['error']['status'] : '';
				$message = isset( $response['error']['message'] ) ? (string) $response['error']['message'] : '';

				wp_send_json_error( array(
					'message' => esc_html__( 'Google rejected the request. Check the Project ID, the API Key, and that the reCAPTCHA Enterprise API is enabled for the project.', 'hide-my-wp' ),
					'details' => array_values( array_filter( array( $status, $message ) ) ),
				) );
			}

			if ( empty( $response['tokenProperties']['valid'] ) ) {
				$reason = isset( $response['tokenProperties']['invalidReason'] ) ? (string) $response['tokenProperties']['invalidReason'] : '';

				wp_send_json_error( array(
					'message' => esc_html__( 'Google rejected the token. Check that the Site Key belongs to this project and is registered for this domain.', 'hide-my-wp' ),
					'details' => array_values( array_filter( array( $reason ) ) ),
				) );
			}

			if ( isset( $response['riskAnalysis']['score'] ) ) {
				$details[] = esc_html__( 'Score', 'hide-my-wp' ) . ': ' . $response['riskAnalysis']['score'];
			}
			if ( isset( $response['tokenProperties']['action'] ) ) {
				$details[] = esc_html__( 'Action', 'hide-my-wp' ) . ': ' . $response['tokenProperties']['action'];
			}
			if ( isset( $response['tokenProperties']['hostname'] ) ) {
				$details[] = esc_html__( 'Hostname', 'hide-my-wp' ) . ': ' . $response['tokenProperties']['hostname'];
			}

		} else {
			$secret = HMWP_Classes_Tools::getValue( 'secret_key' );

			if ( $secret == '' ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Fill in the Secret Key first.', 'hide-my-wp' ) ) );
			}

			/** @var HMWP_Models_Bruteforce_Abstract $service */
			$service  = HMWP_Classes_ObjController::getClass( $type == 'v3' ? 'HMWP_Models_Bruteforce_GoogleV3' : 'HMWP_Models_Bruteforce_GoogleV2' );
			$response = $service->checkToken( $secret, $token );

			if ( empty( $response ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Google could not be reached from this server.', 'hide-my-wp' ) ) );
			}

			if ( empty( $response['success'] ) ) {
				$codes    = isset( $response['error-codes'] ) ? (array) $response['error-codes'] : array();
				$messages = array(
					'missing-input-secret'   => esc_html__( 'The Secret Key is missing.', 'hide-my-wp' ),
					'invalid-input-secret'   => esc_html__( 'The Secret Key is wrong.', 'hide-my-wp' ),
					'invalid-input-response' => esc_html__( 'Google rejected the token. The Site Key and the Secret Key do not belong to the same reCAPTCHA key, or the key type does not match this version.', 'hide-my-wp' ),
					'timeout-or-duplicate'   => esc_html__( 'The token expired or was already used. Run the check again.', 'hide-my-wp' ),
					'bad-request'            => esc_html__( 'Google could not read the request.', 'hide-my-wp' ),
					'browser-error'          => esc_html__( 'Google did not accept this domain for the Site Key. Add the domain of this website to the reCAPTCHA key in the Google console.', 'hide-my-wp' ),
				);

				$message = esc_html__( 'Google rejected the keys.', 'hide-my-wp' );
				foreach ( $codes as $code ) {
					if ( isset( $messages[ $code ] ) ) {
						$message = $messages[ $code ];
						break;
					}
				}

				wp_send_json_error( array( 'message' => $message, 'details' => array_values( $codes ) ) );
			}

			if ( isset( $response['score'] ) ) {
				$details[] = esc_html__( 'Score', 'hide-my-wp' ) . ': ' . $response['score'];
			}
			if ( isset( $response['action'] ) ) {
				$details[] = esc_html__( 'Action', 'hide-my-wp' ) . ': ' . $response['action'];
			}
			if ( isset( $response['hostname'] ) ) {
				$details[] = esc_html__( 'Hostname', 'hide-my-wp' ) . ': ' . $response['hostname'];
			}
		}

		wp_send_json_success( array(
			'message' => esc_html__( 'The reCAPTCHA keys are valid. Google verified a token issued for this Site Key with your credentials.', 'hide-my-wp' ),
			'details' => $details,
		) );
	}

}
