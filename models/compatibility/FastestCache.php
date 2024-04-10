<?php
/**
 * Compatibility Class
 *
 * @file The FastestCache Model file
 * @package HMWP/Compatibility/FastestCache
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_FastestCache extends HMWP_Models_Compatibility_Abstract
{

    public function hookFrontend()
    {
	    //Remove footer content from cache plugins
	    defined('WPFC_REMOVE_FOOTER_COMMENT') || define('WPFC_REMOVE_FOOTER_COMMENT', true);
	    add_filter('wpfc_buffer_callback_filter', array($this, 'findReplaceCache'), PHP_INT_MAX);
	}

}
