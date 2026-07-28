<?php
/**
 * Compatibility Class
 *
 * @file The WP Frontend Admin file
 * @package HMWP/Compatibility/WPFrontendAdmin
 * @since 7.1.08
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility_WPFrontendAdmin extends HMWP_Models_Compatibility_Abstract {

	/**
	 * @throws Exception
	 */
	public function hookAdmin() {

		if ( HMWP_Classes_Tools::getIsset( 'vgfa_source' ) ) {

			if ( HMWP_Classes_Tools::getValue( 'page' ) == 'wu-my-account' ) {
				add_action( 'admin_head', function() {

					if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_click' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_source' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop' ) ) {
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Clicks' )->disableKeysAndClicks();
					}

				}, PHP_INT_MAX );
			} else {
				add_action( 'admin_footer', function() {
					if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_click' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_source' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop' ) ) {
						HMWP_Classes_ObjController::getClass( 'HMWP_Models_Clicks' )->disableKeysAndClicks();
					}

				}, PHP_INT_MAX );
			}

		}


	}

}
