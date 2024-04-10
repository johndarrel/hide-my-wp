<?php
/**
 * Compatibility Class
 *
 * @file The Squirrly Model file
 * @package HMWP/Compatibility/Squirrly
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Squirrly extends HMWP_Models_Compatibility_Abstract
{

	public function hookFrontend() {

		add_filter('sq_option_sq_minify', '__return_true');

		add_filter('sq_buffer', array(HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite'), 'find_replace'), PHP_INT_MAX);

		//Compatibility with Robots.txt - tested 09032022
		if (HMWP_Classes_Tools::getOption('hmwp_robots') && isset($_SERVER['REQUEST_URI'])) {
			//Compatibility with
			if (strpos($_SERVER['REQUEST_URI'], '/robots.txt') !== false) {

				add_filter("sq_custom_robots", function ($robots){
					return str_replace('Squirrly SEO', '', $robots);
				});

			}
		}

	}

}
