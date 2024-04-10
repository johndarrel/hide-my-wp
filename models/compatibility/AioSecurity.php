<?php
/**
 * Compatibility Class
 *
 * @file The AioSecurity Model file
 * @package HMWP/Compatibility/AioSecurity
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Compatibility_AioSecurity extends HMWP_Models_Compatibility_Abstract
{

	public function hookFrontend() {

		add_filter('aiowps_site_lockout_output', array($this, 'aioSecurityMaintenance'), PHP_INT_MAX, 1);

	}

	/**
	 * Compatibility with All In On Security plugin
	 *
	 * @param string $content
	 *
	 * @throws Exception
	 */
	public function aioSecurityMaintenance( $content )
	{
		if (defined('AIO_WP_SECURITY_PATH') ) {
			if (empty($content) ) {
				nocache_headers();
				header("HTTP/1.0 503 Service Unavailable");
				remove_action('wp_head', 'head_addons', 7);

				ob_start();
				$template = apply_filters('aiowps_site_lockout_template_include', AIO_WP_SECURITY_PATH . '/other-includes/wp-security-visitor-lockout-page.php');
				include_once $template;
				$output = ob_get_clean();

				echo HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->find_replace($output);
			} else {
				echo $content;
			}

			exit();
		}
	}

}
