<?php
/**
 * Compatibility Class
 *
 * @file The W3Total Model file
 * @package HMWP/Compatibility/W3Total
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_W3Total extends HMWP_Models_Compatibility_Abstract
{

    public function hookFrontend()
    {

	    if (apply_filters('w3tc_lazyload_is_embed_script', true) ) {
		    add_filter('w3tc_lazyload_is_embed_script', '__return_false', PHP_INT_MAX);
		    add_filter('w3tc_lazyload_embed_script', array($this, 'embedW3TotalCacheLazyLoadscript'), PHP_INT_MAX);
	    }

	    //Don't show comments
	    add_filter('w3tc_can_print_comment', '__return_false', PHP_INT_MAX);
	    //Hook the cached buffer
	    add_filter('w3tc_processed_content', array($this, 'findReplaceCache'), PHP_INT_MAX);

	}

	/**
	 * Compatibility with W3 Total Cache Lazy Load
	 *
	 * @param  $buffer
	 * @return string|string[]|null
	 * @throws Exception
	 */
	public function embedW3TotalCacheLazyLoadscript( $buffer )
	{
		$js_url = plugins_url('pub/js/lazyload.min.js', W3TC_FILE);
		$js_url = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace_url($js_url);

		$fireEvent = 'function(t){var e;try{e=new CustomEvent("w3tc_lazyload_loaded",{detail:{e:t}})}catch(a){(e=document.createEvent("CustomEvent")).initCustomEvent("w3tc_lazyload_loaded",!1,!1,{e:t})}window.dispatchEvent(e)}';
		$config = '{elements_selector:".lazy",callback_loaded:' . $fireEvent . '}';

		$on_initialized_javascript = apply_filters('w3tc_lazyload_on_initialized_javascript', '');

		$on_initialized_javascript_wrapped = '';
		if (!empty($on_initialized_javascript) ) {
			// LazyLoad::Initialized fired just before making LazyLoad global
			// so next execution cycle have it
			$on_initialized_javascript_wrapped =
				'window.addEventListener("LazyLoad::Initialized", function(){' .
				'setTimeout(function() {' .
				$on_initialized_javascript .
				'}, 1);' .
				'});';
		}

		$embed_script =
			'<style>img.lazy{min-height:1px}</style>' .
			'<link rel="preload" href="' . esc_url($js_url) . '" as="script">';

		$buffer = preg_replace(
			'~<head(\s+[^>]*)*>~Ui',
			'\\0' . $embed_script, $buffer, 1
		);

		// load lazyload in footer to make sure DOM is ready at the moment of initialization
		$footer_script =
			'<script>' .
			$on_initialized_javascript_wrapped .
			'window.w3tc_lazyload=1,' .
			'window.lazyLoadOptions=' . $config .
			'</script>' .
			'<script async src="' . esc_url($js_url) . '"></script>';
		$buffer = preg_replace(
			'~</body(\s+[^>]*)*>~Ui',
			$footer_script . '\\0', $buffer, 1
		);

		return $buffer;
	}

}
