<?php
/**
 * Compatibility Class
 *
 * @file The FlyingPress Model file
 * @package HMWP/Compatibility/FlyingPress
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_FlyingPress extends HMWP_Models_Compatibility_Abstract
{

    public function hookFrontend()
    {

	    //Hook the cached buffer
	    add_filter('flying_press_optimization:after', array($this, 'findReplaceCache'), PHP_INT_MAX);

	}

}
