<?php
/**
 * Compatibility Class
 *
 * @file The Autoptimize Model file
 * @package HMWP/Compatibility/Autoptimize
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Autoptimize extends HMWP_Models_Compatibility_Abstract
{

	public function hookFrontend() {

		//don't use smush cdn with automizer
		if (HMWP_Classes_Tools::isPluginActive('wp-smush-pro/wp-smush.php') ) {
			if ($smush = get_option('wp-smush-cdn_status') ) {
				if (isset($smush->cdn_enabled) && $smush->cdn_enabled ) {
					return;
				}
			}
		}

		add_filter('autoptimize_html_after_minify', array($this, 'findReplaceCache'), PHP_INT_MAX);

	}

}
