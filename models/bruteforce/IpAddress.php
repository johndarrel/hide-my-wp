<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force IP Address file
 * @package HMWP/BruteForce/IpAddress
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Bruteforce_IpAddress {

	/**
	 * Retrieves and sets the ip address the person logging in
	 *
	 * @return string The real IP address
	 * @throws Exception
	 */
	public function getIp() {

		/** @var HMWP_Models_Firewall_Server $server */
		$server = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Firewall_Server' );
		return $server->getIp();

	}


}
