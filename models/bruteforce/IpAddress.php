<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force IP Address file
 * @package HMWP/BruteForce/IpAddress
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Bruteforce_IpAddress {

    private $ip;

	/**
	 * Retrieves and sets the ip address the person logging in
	 *
	 * @return string The real IP address
	 * @throws Exception
	 */
	public function getIp() {

		if ( isset( $this->ip ) ) {
			return $this->ip;
		}

		// Get all real IP addressed from valid headers
		$server = HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_Firewall' )->getServerVariableIPs();

		if ( ! empty( $server ) ) {

			// For each IP found on the caller
			foreach ( $server as $ip ) {

                // Only if is not a local IP address
				if ( $ip == '127.0.0.1' || $ip == '::1' || $this->isPrivate( $ip ) ) {
					continue;
				}

				// Set the first valid IP address
				$this->ip = $ip;

				break;
			}
		}

		return $this->ip;
	}


	/**
	 * Checks an IP to see if it is within a private range
	 *
	 * @param  string  $ip
	 *
	 * @return bool
	 */
	public function isPrivate( $ip ) {

		$private_ips = array(
			'10.0.0.0|10.255.255.255', // single class A network
			'172.16.0.0|172.31.255.255', // 16 contiguous class B network
			'192.168.0.0|192.168.255.255', // 256 contiguous class C network
			'169.254.0.0|169.254.255.255', // Link-local address also referred to as Automatic Private IP Addressing
			'127.0.0.0|127.255.255.255' // localhost
		);

		$long_ip = ip2long( $ip );
		if ( $long_ip != - 1 ) {

			foreach ( $private_ips as $private_ip ) {
				list ( $start, $end ) = explode( '|', $private_ip );

				// If it is a private IP address
				if ( $long_ip >= ip2long( $start ) && $long_ip <= ip2long( $end ) ) {
					return true;
				}
			}
		}

		return false;
	}


}
