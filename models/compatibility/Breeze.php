<?php
/**
 * Compatibility Class
 *
 * @file The Breeze Model file
 * @package HMWP/Compatibility/Breeze
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_Breeze extends HMWP_Models_Compatibility_Abstract
{

	public function hookFrontend() {

		add_filter('breeze_minify_content_return', array($this, 'findReplaceCache'), PHP_INT_MAX);

		$breezeOptions = get_option('breeze_file_settings');
		if(isset($breezeOptions['breeze-minify-css']) && $breezeOptions['breeze-minify-css']){
			add_filter('hmwp_process_find_replace', '__return_false');

			add_filter('breeze_minify_content_return', function ($content){
				add_filter('hmwp_process_find_replace', '__return_true');
				return $this->findReplaceCache($content);
			}, PHP_INT_MAX);

		}

	}

}
