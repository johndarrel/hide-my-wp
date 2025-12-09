<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Model file
 * @package HMWP/Models/BruteForce
 * @since 4.2.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Brute {

	/**
	 * Get the name of the active Brute Force protection
	 * @return string|void
	 */
	public function getName() {
		if ( HMWP_Classes_Tools::getOption( 'brute_use_math' ) ) {
			return 'Math';
		}

		if ( HMWP_Classes_Tools::getOption( 'brute_use_google_enterprise' ) ) {
			if ( HMWP_Classes_Tools::getOption( 'brute_use_google' ) ) {
				return 'Google';
			}
		} else {
			if ( HMWP_Classes_Tools::getOption( 'brute_use_captcha' ) ) {
				return 'GoogleV2';
			} elseif ( HMWP_Classes_Tools::getOption( 'brute_use_captcha_v3' ) ) {
				return 'GoogleV3';
			}
		}
	}

	/**
	 * Get the name of the active Brute Force protection
	 *
	 * @return HMWP_Models_Bruteforce_Math|HMWP_Models_Bruteforce_GoogleV2|HMWP_Models_Bruteforce_GoogleV3 of the selected Brute Force protection type
	 *
	 * @throws Exception
	 */
	public function getInstance() {

		// Get the active Brute Force name
		if ( ! $this->getName() ) {
			return HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Math' );
		}

		return HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_' . $this->getName() );
	}

	/**
	 * Process the brute call
	 *
	 * @param  string  $action  'check_ip', 'clear_ip', or 'failed_attempt'
	 *
	 * @return array|mixed
	 * @throws Exception
	 */
	public function processIp( $action = 'check_ip' ) {

		// Get current IP
		$ip = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_IpAddress' )->getIp();

		// Check if there is a record for this IP in database already
		if ( ! $response = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->get( $ip ) ) {
			$response = array();
		}

		// Take action based on the action
		switch ( $action ) {
			case 'check_ip':

				$response['status'] = ( $response['status'] ?? 'ok' );

				// Never block login from whitelisted IPs
				if ( HMWP_Classes_Tools::isWhitelistedIP( $ip ) ) {
					$response['status'] = 'whitelist';
				}elseif ( HMWP_Classes_Tools::isBlacklistedIP( $ip ) ) {
					// Check if the IP address is already banned by the admin
					$response['status'] = 'blocked';
				}

				break;

			case 'failed_attempt':

				// Get attempts
				$attempts = (int) ( $response['attempts'] ?? 1 );

				// If reached the maximum number of fail attempts
				if ( ! HMWP_Classes_Tools::isWhitelistedIP( $ip ) &&
				     $attempts >= HMWP_Classes_Tools::getOption( 'brute_max_attempts' ) ) {

					// Block current IP address
					$this->blockIp( $ip );

					// Show blocked message
					$this->bruteForceBlock();

				} else {

					// Increase fail attempts
					$attempts = $attempts + 1;

					// Save the attempt in database for this IP address
					$response['ip'] = $ip;
					$response['attempts'] = $attempts;
					$response['status']   = 'ok';

					HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->save( $ip, $response );
				}

				break;

			case 'clear_ip':

				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->delete( $ip );

				break;

		}

		return $response;
	}

	/**
	 * Block current IP address
	 *
	 * @param $ip
	 *
	 * @return void
	 * @throws Exception
	 */
	public function blockIp( $ip ) {

		// Get current IP info from database
		if ( ! $response = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->get( $ip ) ) {
			$response = array();
		}

		// Get the attempts
		$attempts = (int) ( $response['attempts'] ?? 1 );

		// Add all the info needed for this IP address
		$response['ip']       = $ip;
		$response['headers']  = json_encode( HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Firewall' )->getServerVariableIPs() );
		$response['attempts'] = $attempts;
		$response['status']   = 'blocked';

		// Save the info into database
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->save( $ip, $response );

	}

	/**
	 * Check the brute force attempts
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	public function bruteForceCheck() {

		$response = $this->processIp();

		if ( $response['status'] == 'blocked' ) {
			$this->bruteForceBlock();
		}

		return $response;
	}

	/**
	 * Show the error message on IP address banned
	 *
	 * @return void
	 * @throws Exception
	 */
	public function bruteForceBlock() {

		do_action( 'hmwp_kill_login', HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_IpAddress' )->getIp() );

		wp_ob_end_flush_all();
		wp_die( HMWP_Classes_Tools::getOption( 'hmwp_brute_message' ), esc_html__( 'IP Blocked by' . ' ' . HMWP_Classes_Tools::getOption( 'hmwp_plugin_name' ), 'hide-my-wp' ), array( 'response' => 403 ) );
	}

	/**
	 * Process the IP and call Brute Force
	 *
	 * @deprecated since 8.2
	 *
	 * @return void
	 * @throws Exception
	 */
	public function brute_call( $action = 'check_ip' ) {
		$this->processIp( $action );
	}
}
