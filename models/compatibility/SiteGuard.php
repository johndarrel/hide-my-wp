<?php
/**
 * Compatibility Class
 *
 * @file The SiteGuard Model file
 * @package HMWP/Compatibility/SiteGuard
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_SiteGuard extends HMWP_Models_Compatibility_Abstract
{

	public function hookAdmin() {
		if(get_option("siteground_optimizer_combine_css", false) ||
		   get_option("siteground_optimizer_combine_javascript", false)) {
			add_action( 'hmwp_mappsettings_saved', array( $this, 'cacheMapping' ) );
			add_action( 'hmwp_settings_saved', array( $this, 'cacheMapping' ) );
		}
	}

	public function hookFrontend() {

		//remove custom login if already set in HMWP Ghost to prevent errors
		add_filter("pre_option_siteguard_config", function ($siteguard_config) {

			if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
				$siteguard_config['renamelogin_enable'] = 0;
			}

			return $siteguard_config;
		});

		if(get_option("siteground_optimizer_combine_css", false) ||
		   get_option("siteground_optimizer_combine_javascript", false)){

			if(HMWP_Classes_Tools::doChangePaths()) {
				add_filter('hmwp_process_buffer', '__return_false');
				add_filter('hmwp_process_find_replace', '__return_false');
				add_action('init', array($this, 'startBuffer'), 1);
				add_action('shutdown', array($this, 'shutdownBuffer'), PHP_INT_MAX);
			}

		}

	}

	/**
	 * Start the buffer listener
	 *
	 * @throws Exception
	 */
	public function startBuffer()
	{

		ob_start(array($this, 'getBuffer'));

	}

	/**
	 * Listen shotdown buffer when SiteGuard is active
	 * @return void
	 * @throws Exception
	 */
	public function shutdownBuffer(){

		$buffer = ob_get_contents();
		$buffer = $this->getBuffer( $buffer );

		if($buffer <> '') {
			echo $buffer;
			exit();
		}

	}

	/**
	 * Modify the output buffer
	 * Only text/html header types
	 *
	 * @param $buffer
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function getBuffer( $buffer )
	{

		//Check if other plugins already did the cache
		try {

			//If the content is HTML
			if (HMWP_Classes_Tools::isContentHeader(array('text/html')) ) {
				//If the user set to change the paths for logged users
				$rewriteModel = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite');
				add_filter('hmwp_process_find_replace', '__return_true');
				$buffer = $rewriteModel->find_replace($buffer);
			}

		} catch ( Exception $e ) {
			return $buffer;
		}

		//Return the buffer to HTML
		return apply_filters('hmwp_buffer', $buffer);
	}


	/**
	 * Create the WP-Rocket Burst Mapping
	 *
	 * @throws Exception
	 */
	public function cacheMapping()
	{

		if (HMWP_Classes_Tools::getDefault('hmwp_wp-content_url') <> HMWP_Classes_Tools::getOption('hmwp_wp-content_url')) {
			//Add the URL mapping for wp-rocket plugin
			$hmwp_url_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_url_mapping'), true);

			//if no mapping is set allready
			if (HMWP_Classes_Tools::isMultisites()) {
				global $wpdb;

				$blogs = $wpdb->get_results("SELECT blog_id FROM " . $wpdb->blogs);

				if(!empty($blogs)) {
					foreach ($blogs as $blog) {
						$original_path = '/' . HMWP_Classes_Tools::getDefault('hmwp_wp-content_url') . '/' . HMWP_Classes_Tools::getDefault('hmwp_upload_url') . '/sites/' . $blog->blog_id . '/';
						$final_path = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace_url($original_path);

						//mapp the wp-rocket busting wp-content
						if (empty($hmwp_url_mapping['from']) || !in_array('/' . trim($final_path, '/') . '/siteground-optimizer-assets/', $hmwp_url_mapping['from'])) {
							$hmwp_url_mapping['from'][] = '/' . trim($final_path, '/') . '/siteground-optimizer-assets/';
							$hmwp_url_mapping['to'][] = '/' . trim($final_path, '/') . '/' . substr(md5('siteground-optimizer-assets'), 0, 10) . '/';
						}
					}
				}
			} else {
				$original_path = '/' . HMWP_Classes_Tools::getDefault('hmwp_wp-content_url') . '/' . HMWP_Classes_Tools::getDefault('hmwp_upload_url') . '/';
				$final_path = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace_url($original_path);

				//mapp the wp-rocket busting wp-content
				if (empty($hmwp_url_mapping['from']) || !in_array('/' . trim($final_path, '/') . '/siteground-optimizer-assets/', $hmwp_url_mapping['from'])) {
					$hmwp_url_mapping['from'][] = '/' . trim($final_path, '/') . '/siteground-optimizer-assets/';
					$hmwp_url_mapping['to'][] = '/' . trim($final_path, '/') . '/' . substr(md5('siteground-optimizer-assets'), 0, 10) . '/';
				}
			}

			HMWP_Classes_ObjController::getClass('HMWP_Models_Settings')->saveURLMapping($hmwp_url_mapping['from'], $hmwp_url_mapping['to']);
		}
	}

}
