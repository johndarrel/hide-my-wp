<?php
/**
 * Compatibility Class
 *
 * @file The Nitropack Model file
 * @package HMWP/Compatibility/Nitropack
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Nitropack extends HMWP_Models_Compatibility_Abstract
{
	public function __construct() {
		parent::__construct();

		//check again if the options are active after the filrter are applied
		if(HMWP_Classes_Tools::getOption('hmwp_disable_click')
		   || HMWP_Classes_Tools::getOption('hmwp_disable_inspect')
		   || HMWP_Classes_Tools::getOption('hmwp_disable_source')
		   || HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste')
		   || HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop')
		) {

			add_action('wp_head', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Clicks'), 'disableKeysAndClicks'), PHP_INT_MAX);
			remove_action('wp_footer', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Clicks'), 'disableKeysAndClicks'), PHP_INT_MAX);

		}
	}

    public function hookAdmin()
    {
	    //Doesn't work when blocking CSS and JS on old paths
	    add_filter('hmwp_common_paths_extensions', function ( $alltypes ) {
		    return array_diff( $alltypes, array( '\.css', '\.scss', '\.js' ) );
	    });

	}

}
