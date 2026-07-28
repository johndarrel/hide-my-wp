<?php
/**
 * Compatibility Class
 *
 * @file The Breeze Model file
 * @package HMWP/Compatibility/Breeze
 * @since 7.0.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Compatibility_Cmp extends HMWP_Models_Compatibility_Abstract {

	public function __construct() {
		parent::__construct();

		add_filter( 'hmwp_priority_buffer', '__return_true' );

		add_action( 'cmp_footer', function() {
			if ( HMWP_Classes_Tools::getOption( 'hmwp_disable_click' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_inspect' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_source' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_copy_paste' ) || HMWP_Classes_Tools::getOption( 'hmwp_disable_drag_drop' ) ) {
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Clicks' )->disableKeysAndClicks();
			}

		}, PHP_INT_MAX );

	}

}
