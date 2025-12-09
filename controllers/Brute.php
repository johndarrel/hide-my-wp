<?php
/**
 * Brute Force Protection
 * Called when the Brute Force Protection is activated
 *
 * @file  The Brute Force file
 * @package HMWP/BruteForce
 * @since 4.2.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Class HMWP_Controllers_Brute
 *
 * Handles brute force protection mechanisms including login, registration, and lost password
 * attempts. Integrates various captcha methods to safeguard against automated attacks.
 */
class HMWP_Controllers_Brute extends HMWP_Classes_FrontController {

	/**
	 * Load all Brute Force classes
	 *
	 * @throws Exception
	 */
	public function __construct() {
		// Call parent constructor
		parent::__construct();

		// If the safe parameter is set, clear the banned IPs and let the default paths
		if ( HMWP_Classes_Tools::calledSafeUrl( ) ) {
			return;
		}

		// Load Brute Force for shortcodes
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Shortcode' );

		// Check  Brute Force on login
		if ( HMWP_Classes_Tools::getOption( 'hmwp_bruteforce_login' ) ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Login' );
		}
		// Check  Brute Force on lost password
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

	}

	/**
	 * Hook into the front-end initialization process.
	 *
	 * @return void
	 */
	public function hookFrontinit() {
		// Only if the user is not logged in
		if ( function_exists( 'is_user_logged_in' ) && ! is_user_logged_in() ) {

			// Load the Multilingual support for frontend
			HMWP_Classes_Tools::loadMultilanguage();

			// Check brute force
			$this->model->bruteForceCheck();
		}
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

		// Handle different actions
		switch ( HMWP_Classes_Tools::getValue( 'action' ) ) {

			case 'hmwp_brutesettings':
				// Save the brute force related settings
				if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveValues( $_POST );
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

					HMWP_Classes_Tools::emptyCache();
					HMWP_Classes_Error::setNotification( esc_html__( 'Saved' ), 'success' );
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
			case 'hmwp_blockedips':
				// Get the list of blocked IPs and send as JSON response if it's an Ajax request
				if ( HMWP_Classes_Tools::isAjax() ) {
					wp_send_json_success( $this->getBlockedIpsTable() );
				}

				break;

		}
	}

	/**
	 * Retrieves and constructs an HTML table of blocked IP addresses and their details.
	 *
	 * @return string An HTML string representing a table with blocked IP addresses, including columns for IP address, number of failed attempts, hostname, and options for unlocking.
	 * @throws Exception
	 */
	public function getBlockedIpsTable() {
		$data = '<table class="table table-striped" >';
		$ips  = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->getBlockedIps();
		$data .= "<tr>
                    <th>" . esc_html__( 'Cnt', 'hide-my-wp' ) . "</th>
                    <th>" . esc_html__( 'IP', 'hide-my-wp' ) . "</th>
                    <th>" . esc_html__( 'Fail Attempts', 'hide-my-wp' ) . "</th>
                    <th>" . esc_html__( 'Options', 'hide-my-wp' ) . "</th>
                 </tr>";
		if ( ! empty( $ips ) ) {
			$cnt = 1;
			foreach ( $ips as $ip ) {
				$data .= "<tr>
                        <td>" . $cnt . "</td>
                        <td>{$ip['ip']}</td>
                        <td>{$ip['attempts']}</td>
                        <td class='p-2'> <form method=\"POST\">
                                " . wp_nonce_field( 'hmwp_deleteip', 'hmwp_nonce', true, false ) . "
                                <input type=\"hidden\" name=\"action\" value=\"hmwp_deleteip\" />
                                <input type=\"hidden\" name=\"ip\" value=\"" . $ip['ip'] . "\" />
                                <input type=\"submit\" class=\"btn rounded-0 btn-sm btn-light save no-p-v\" value=\"Unlock\" />
                            </form>
                        </td>
                     </tr>";
				$cnt ++;
			}
		} else {
			$data .= "<tr>
                                <td colspan='5'>" . esc_html__( 'No blacklisted ips', 'hide-my-wp' ) . "</td>
                             </tr>";
		}
		$data .= '</table>';

		return $data;
	}

}
