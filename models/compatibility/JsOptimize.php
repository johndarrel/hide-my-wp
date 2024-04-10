<?php
/**
 * Compatibility Class
 *
 * @file The JsOptimize Model file
 * @package HMWP/Compatibility/JsOptimize
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_JsOptimize extends HMWP_Models_Compatibility_Abstract
{

    public function hookFrontend()
    {

	    if (!defined('JCH_CACHE_DIR')) {
		    //Initialize WordPress Filesystem
		    $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();
		    define('JCH_CACHE_DIR', $wp_filesystem->wp_content_dir() . 'cache/mycache/');
	    }

	    add_filter('jch_optimize_save_content', array($this, 'findReplaceCache'), PHP_INT_MAX);

	}

}
