<?php
/**
 * Compatibility Class
 *
 * @file The ConfirmEmail Model file
 * @package HMWP/Compatibility/ConfirmEmail
 * @since 7.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility_ConfirmEmail extends HMWP_Models_Compatibility_Abstract {

	public function hookFrontend() {
		add_action( 'init', array( $this, 'checkAppThemesConfirmEmail' ) );
	}

	/**
	 * Compatibility with Confirm Email from AppThemes
	 *
	 * call the appthemes_confirm_email_template_redirect
	 * for custom login paths
	 */
	public function checkAppThemesConfirmEmail() {

		if ( HMWP_Classes_Tools::getIsset( 'action' ) ) {
			if ( function_exists( 'appthemes_confirm_email_template_redirect' ) ) {
				appthemes_confirm_email_template_redirect();
			}
		}

	}

}
