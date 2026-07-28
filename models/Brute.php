<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Model file
 * @package HMWP/Models/BruteForce
 * @since 4.2.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

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
	 * Generate a unique Request ID for this request.
	 *
	 * @return false|string
	 */
	public function getRid() {
		/** @var HMWP_Models_Firewall_Threats $threats */
		$threats = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Threats' );

		return $threats->getRid();
	}

	/**
	 * Get the name of the active Brute Force protection
	 *
	 * @return false|object of the selected Brute Force protection type
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
	 * @param string $action 'check_ip', 'clear_ip', or 'failed_attempt'
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
				if ( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Rules' )->isWhitelistedIP( $ip ) ) {
					$response['status'] = 'whitelist';
				} elseif ( HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Rules' )->isBlacklistedIP( $ip ) ) {
					// Check if the admin already bans the IP address
					$response['status'] = 'blocked';
				}

				break;

			case 'failed_attempt':

				// Get attempts
				$attempts = (int) ( $response['attempts'] ?? 1 );

				// If reached the maximum number of fail attempts
				if ( ! HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Rules' )->isWhitelistedIP( $ip ) && $attempts >= HMWP_Classes_Tools::getOption( 'brute_max_attempts' ) ) {

					/** @var HMWP_Models_Firewall_Rules $firewallRules */
					$firewallRules = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Rules' );
					if ( ! $firewallRules->checkWhitelistRule( 'BF_LOGIN_EXCEEDED' ) ) {

						// Block the current IP address
						$this->blockIp( $ip );

						do_action( 'hmwp_threat_detected', array(
							'code'    => 'BF_LOGIN_EXCEEDED',
							'area'    => 'login',
						) );

						// Show a blocked message
						$this->bruteForceBlock();
					}

				} else {

					// Increase fail attempts
					$attempts = $attempts + 1;

					// Save the attempt in database for this IP address
					$response['ip']       = $ip;
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

		/** @var HMWP_Models_Firewall_Server $server */
		$server = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Server' );

		// Add all the info needed for this IP address
		$response['ip']       = $ip;
		$response['headers']  = json_encode( $server->getServerVariableIPs() );
		$response['attempts'] = $attempts;
		$response['status']   = 'blocked';

		// Save the info into database
		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_Database' )->save( $ip, $response );

		// Log the block IP on the server if the Events Log is active
		if ( HMWP_Classes_Tools::getOption( 'hmwp_activity_log' ) ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_EventsLog' )->save( 'block_ip', array( 'ip' => $ip ) );
		}
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

		// Avoid showing load_textdomain_just_in_time on block page
		global $wp_actions;
		$wp_actions['after_setup_theme'] = 1;

		// Load the Multilingual support for frontend
		HMWP_Classes_Tools::loadMultilanguage();

		// Get current IP
		$ip      = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Bruteforce_IpAddress' )->getIp();
		$rid     = $this->getRid();
		$name    = __( 'Brute Force Protection', 'hide-my-wp' );
		$message = (string) HMWP_Classes_Tools::getOption( 'hmwp_brute_message' );

		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; //phpcs:ignore
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		if ( $path === '' ) {
			$path = '/';
		}

		// Trim and sanitize displayed path
		$path = wp_strip_all_tags( $path );
		if ( strlen( $path ) > 120 ) {
			$path = substr( $path, 0, 120 ) . '...';
		}

		// Set threat as prevented
		add_filter( 'hmwp_threat_prevented', '__return_true');

		/** @var HMWP_Controllers_Firewall $firewall */
		$firewall = HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Firewall' );
		$firewall->renderFirewallBlockPage( array(
			'title'      => esc_html__( 'This IP was blocked for security reasons', 'hide-my-wp' ),
			'message'     => esc_html( $message ),
			'name'        => esc_html( $name),
			'ip'          => esc_attr( $ip ),
			'path'        => esc_attr( $path ),
			'rid'         => esc_attr( $rid ),
			'statusCode'  => 403,
		) );

		exit;

	}

	/**
	 * Process the IP and call Brute Force
	 *
	 * @return void
	 * @throws Exception
	 * @deprecated since 8.2
	 *
	 */
	public function brute_call( $action = 'check_ip' ) {
		$this->processIp( $action );
	}
}
