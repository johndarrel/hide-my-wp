<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Math Recaptcha file
 * @package HMWP/BruteForce/Math
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Bruteforce_Database {

	private $prefix = 'hmwp_brute_';

	/**
	 * Save the transient with the blocked IP in database
	 *
	 * @param  string  $ip  The verified IP address
	 * @param string $value Info about the login attempts, headers
	 *
	 * @return bool
	 */
	public function save( $ip, $value ) {

		if ( isset( $ip ) ) {

			$transient = $this->prefix . md5( $ip );

			$expiration = (int) HMWP_Classes_Tools::getOption( 'brute_max_timeout' );

			if ( HMWP_Classes_Tools::isMultisites() && ! is_main_site() ) {
				switch_to_blog( $this->getMainBlogId() );
				$return = set_transient( $transient, $value, $expiration );
				restore_current_blog();

				return $return;
			}

			return set_transient( $transient, $value, $expiration );

		}

		return false;

	}


	/**
	 * Get the saved transient from database
	 *
	 * @param string $ip The verified IP address
	 *
	 * @return mixed
	 */
	public function get( $ip ) {

		if ( isset( $ip ) ) {

			$transient = $this->prefix . md5( $ip );

			if ( HMWP_Classes_Tools::isMultisites() && ! is_main_site() ) {
				switch_to_blog( $this->getMainBlogId() );
				$return = get_transient( $transient );
				restore_current_blog();

				return $return;
			}

			return get_transient( $transient );

		}

		return false;
	}

	/**
	 * Delete the transient from database
	 *
	 * @param  string  $ip  The verified IP address
	 *
	 * @return bool
	 */
	public function delete( $ip ) {

		$transient = $this->prefix . md5( $ip );

		if ( HMWP_Classes_Tools::isMultisites() && ! is_main_site() ) {
			switch_to_blog( $this->getMainBlogId() );
			$return = delete_transient( $transient );
			restore_current_blog();

			return $return;
		}

		return delete_transient( $transient );
	}

	/**
	 * If we're in a multisite network, return the blog ID of the primary blog
	 *
	 * @return int
	 */
	public function getMainBlogId() {

		if ( defined( 'BLOG_ID_CURRENT_SITE' ) ) {
			return BLOG_ID_CURRENT_SITE;
		}

		return 1;
	}

	/**
	 * Get all blocked IPs
	 *
	 * @return array
	 */
	public function getBlockedIps() {
		global $wpdb;
		$ips = array();

		// Get the transient that match the plugin brute force pattern
		$pattern = '_transient_' . $this->prefix;

		//check 20 keyword at one time
		$sql = $wpdb->prepare( "SELECT `option_value` FROM `{$wpdb->options}` WHERE (`option_name` LIKE %s) ORDER BY `option_id` DESC", $pattern . '%' );

		if ( $rows = $wpdb->get_results( $sql ) ) {

			foreach ( $rows as $row ) {
				if($row->option_value = maybe_unserialize( $row->option_value ) ){
					if ( $transient_value = $this->get( $row->option_value['ip'] ) ) {

						if ( isset( $transient_value['status'] ) && $transient_value['status'] == 'blocked' ) {
							$ips[] = $transient_value;
						}
					}
				}

			}
		}

		return $ips;
	}

	/**
	 * Clear the block IP table
	 */
	public function clearBlockedIPs() {
		$ips = $this->getBlockedIps();

		if ( ! empty( $ips ) ) {
			foreach ( $ips as $ip ) {
				$this->delete( $ip['ip'] );
			}
		}
	}
}
