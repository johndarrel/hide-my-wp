<?php
/**
 * Logging Class
 * Called on Events Log
 *
 * @file The Events Log file
 * @package HMWP/Events
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Controllers_EventsLog extends HMWP_Classes_FrontController {

	/**
	 * Constructor. Registers WP hooks for recording user login and activity events.
	 *
	 * Attaches to the authentication pipeline, WooCommerce login errors, and the
	 * wp_loaded action to capture and persist events to the events log.
	 *
	 * @throws Exception
	 */
	public function __construct() {
		parent::__construct();

        // Hook the login process to authenticate
		add_filter( 'authenticate', array( $this, 'authenticate' ), 99, 1 );

        // Apply filter for WooCommerce login process
		add_filter( 'woocommerce_process_login_errors', array( $this, 'authenticate' ), 99, 1 );

        // Hook log function to wp_loaded action
		add_action( 'wp_loaded', array( $this, 'run' ), 9 );

		//Save the login method in the events log
		add_action( 'hmwp_user_auth_success', function ( $user, $method ) {
			$values = array(
				'username' => $user->user_login,
				'referer' => $method,
			);

			$this->model->save( 'login', $values, $user->ID );
		}, 11, 2 );
	}

	/**
	 * Initializes the hook for setting up the database table.
	 *
	 * @return void
	 * @throws Exception If the database operation fails.
	 */
	public function hookInit() {

		/** @var HMWP_Models_Firewall_Database $database */
		$database = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Database' );
		$database->maybeCreateTable();

	}

	/**
	 * Function called on login process
	 *
	 * @param null $user
	 *
	 * @return null
	 */
	public function authenticate( $user = null ) {

		if ( empty( $_POST ) ) { //phpcs:ignore
			return $user;
		}

		//set default action name
		$action = 'login';

        // If there is an error in the user authentication
        if ( is_wp_error( $user ) ) {
			if ( method_exists( $user, 'get_error_codes' ) ) {
				$codes = $user->get_error_codes();
				if ( ! empty( $codes ) ) {
					foreach ( $codes as $action ) {
                        // Log the authentication process error
						$this->model->save( $action );
					}
				}
			}

			return $user;
		}

        // Log the successful authentication process
		$this->model->save( $action );

		return $user;
	}

	/**
	 * Function called on user events
	 */
	public function run() {

		try {

			// Log user activity if there is an action value
			if ( $action = HMWP_Classes_Tools::getValue( 'action' ) ) {

				// Return if both POST and GET are empty
                if ( empty( $_POST ) && empty( $_GET ) ) { //phpcs:ignore
					return;
				}

				// Log the user activity
				$this->model->save( $action );

			}
		} catch ( Exception $e ) {
            // Handle exception (optional)
        }

	}

}
