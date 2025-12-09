<?php
/**
 * Compatibility Class
 *
 * @file The WP Social
 * * @package HMWP/Compatibility/WPSocial
 * * @since 8.2.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Compatibility_WPSocial extends HMWP_Models_Compatibility_Abstract {

	public function __construct() {
		add_filter('login_body_class', array($this, 'bodyClass'));
	}

	public function bodyClass($classes) {
		global $pagenow;

		if ( !in_array( 'login-action-login', $classes ) ) {
			return $classes;
		}

		$pagenow = 'wp-login.php';

		return $classes;
	}

}
