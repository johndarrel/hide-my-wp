<?php
/**
 * Compatibility Class
 *
 * @file The PowerCache Model file
 * @package HMWP/Compatibility/PowerCache
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_PowerCache extends HMWP_Models_Compatibility_Abstract
{

	public function hookFrontend() {

		global $powered_cache_options;

		add_filter('powered_cache_page_caching_buffer', array($this, 'findReplaceCache'), PHP_INT_MAX);

		if (isset($powered_cache_options) ) {
			$powered_cache_options['show_cache_message'] = false;
		}

	}

}
