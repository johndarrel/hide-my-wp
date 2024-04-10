<?php
/**
 * Compatibility Class
 *
 * @file The WpDefender Model file
 * @package HMWP/Compatibility/WpDefender
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_WpDefender extends HMWP_Models_Compatibility_Abstract
{

	public function __construct() {
		parent::__construct();

		add_action('login_form_defender-verify-otp', function () {

			if (!isset($_SERVER['REQUEST_METHOD']) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
				return;
			}

			$_POST['_wpnonce'] = wp_create_nonce('verify_otp');

		}, 9);

	}

	public function hookFrontend() {

		add_filter('wd_mask_login_enable', '__return_false', PHP_INT_MAX, 0);

	}

}
