<?php
/**
 * Logging Class
 * Called on Events Log
 *
 * @file The Events Log file
 * @package HMWP/Events
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Controllers_Log extends HMWP_Classes_FrontController {

	public function __construct() {
		parent::__construct();
		// Hook the login process to authenticate
		add_filter( 'authenticate', array( $this, 'authenticate' ), 99, 1 );

		// Apply filter for WooCommerce login process
		apply_filters( 'woocommerce_process_login_errors', array( $this, 'authenticate' ), 99, 1 );

		// Hook log function to wp_loaded action
		add_action( 'wp_loaded', array( $this, 'listenEvents' ), 9 );
	}

	/**
	 * Admin actions
	 */
	public function action() {
		parent::action();

		// Save options if the action is 'hmwp_logsettings'
		if ( HMWP_Classes_Tools::getValue( 'action' ) == 'hmwp_logsettings' ) {
			HMWP_Classes_Tools::saveOptions( 'hmwp_activity_log', HMWP_Classes_Tools::getValue( 'hmwp_activity_log', 0 ) );
			HMWP_Classes_Tools::saveOptions( 'hmwp_activity_log_roles', HMWP_Classes_Tools::getValue( 'hmwp_activity_log_roles', array() ) );

			// Clear the cache if there are no errors
			if ( ! HMWP_Classes_Tools::getOption( 'error' ) ) {

				if ( ! HMWP_Classes_Tools::getOption( 'logout' ) ) {
					HMWP_Classes_Tools::saveOptionsBackup();
				}

				HMWP_Classes_Tools::emptyCache();
				HMWP_Classes_Error::setNotification( esc_html__( 'Saved' ), 'success' );
			}
		}
	}

	/**
	 * Function called on login process
	 *
	 * @param null $user
	 *
	 * @return null
	 */
	public function authenticate( $user = null ) {
		if ( empty( $_POST ) ) {
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
	public function listenEvents() {

		try {
			// Log user activity if there is an action value
			if ( HMWP_Classes_Tools::getValue( 'action' ) ) {

				// Return if both POST and GET are empty
				if ( empty( $_POST ) && empty( $_GET ) ) {
					return;
				}

				// Get current user
				$current_user = wp_get_current_user();

				// If user is logged in and has roles
				if ( isset( $current_user->user_login ) && is_array( $current_user->roles ) ) {

					// Check if user roles match the allowed roles for logging
					$user_roles   = $current_user->roles;
					$option_roles = ( array ) HMWP_Classes_Tools::getOption( 'hmwp_activity_log_roles' );

					// If no user roles match the allowed roles, return
					if ( ! empty( $option_roles ) && ! empty( $user_roles ) ) {
						if ( ! array_intersect( $user_roles, $option_roles ) ) {
							return;
						}
					}

					// Get the user role from the roles array
					$user_role = '';
					if ( ! empty( $user_roles ) ) {
						$user_role = current( $user_roles );
					}

					// Log the user action with username and role
					$values = array(
						'username' => $current_user->user_login,
						'role'     => $user_role,
					);

					$this->model->save( HMWP_Classes_Tools::getValue( 'action' ), $values );

				}
			}
		} catch ( Exception $e ) {
			// Handle exception (optional)
		}

	}

}
