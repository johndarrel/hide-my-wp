<?php
/**
 * Compatibility Class
 *
 * @file The UsersWP Model file
 * @package HMWP/Compatibility/UsersWP
 * @since 8.0
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Compatibility_UsersWP extends HMWP_Models_Compatibility_Abstract {

	public function hookFrontend() {
		// Get the active brute force class
		$bruteforce = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' )->getInstance();

		add_action('uwp_template_fields', array($bruteforce, 'head'), 99, 1);
		add_action('uwp_template_fields', array($bruteforce, 'form'), 99, 1);
	}

}
