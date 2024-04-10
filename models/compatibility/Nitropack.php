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

    public function hookAdmin()
    {
	    //Doesn't work when blocking CSS and JS on old paths
	    add_filter('hmwp_common_paths_extensions', function ( $alltypes ) {
		    return array_diff( $alltypes, array( '\.css', '\.scss', '\.js' ) );
	    });

	}

}
