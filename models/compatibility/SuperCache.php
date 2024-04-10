<?php
/**
 * Compatibility Class
 *
 * @file The SuperCache Model file
 * @package HMWP/Compatibility/SuperCache
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_SuperCache extends HMWP_Models_Compatibility_Abstract
{

    public function hookFrontend()
    {

	    //Hook the cached buffer
	    add_filter('wpsupercache_buffer', array($this, 'findReplaceCache'), PHP_INT_MAX);

	}

}
